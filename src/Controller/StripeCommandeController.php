<?php

namespace App\Controller;

use App\Service\StripeService;
use App\Service\PanierService;
use App\Repository\CommandeRepository;
use App\Entity\Commande;
use App\Entity\CommandeItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/stripe/commande', name: 'stripe_commande_')]
class StripeCommandeController extends AbstractController
{
    #[Route('/checkout', name: 'checkout', methods: ['POST'])]
    public function checkout(Request $request, StripeService $stripeService, PanierService $panierService, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté'], 401);
        }

        // Récupérer les données JSON envoyées
        $data = json_decode($request->getContent(), true);
        
        // Récupérer le panier via le service
        $panier = $panierService->getPanierOrCreate($user);
        
        if ($panier->getItems()->count() === 0) {
            return $this->json(['error' => 'Votre panier est vide'], 400);
        }

        // Créer la commande
        $commande = new Commande();
        $commande->setUser($user);
        $commande->setStatut('en_attente');
        
        // Ajouter les informations d'adresse si fournies
        if (isset($data['adresse'])) {
            $commande->setAdresseLivraison($data['adresse']);
        }
        if (isset($data['codePostal'])) {
            $commande->setCodePostal($data['codePostal']);
        }
        if (isset($data['ville'])) {
            $commande->setVille($data['ville']);
        }
        if (isset($data['pays'])) {
            $commande->setPays($data['pays']);
        }
        $commande->setMethodePaiement('carte_bancaire');

        // Ajouter les items du panier à la commande
        $items = [];
        $total = 0.0;

        foreach ($panier->getItems() as $panierItem) {
            $livre = $panierItem->getLivre();
            
            // Créer l'item de commande
            $commandeItem = new CommandeItem();
            $commandeItem->setCommande($commande);
            $commandeItem->setLivre($livre);
            $commandeItem->setQuantite($panierItem->getQuantite());
            $commandeItem->setPrixUnitaire($panierItem->getPrixUnitaire());
            
            $commande->addItem($commandeItem);
            $total += $commandeItem->getSubTotal();
            
            // Préparer les items pour Stripe
            $items[] = [
                'name' => $livre->getTitre(),
                'price' => $panierItem->getPrixUnitaire(),
                'quantity' => $panierItem->getQuantite(),
                'image' => $livre->getImage() ?? '',
            ];
        }

        $commande->setTotal($total);
        $em->persist($commande);
        $em->flush();

        // Créer la session Stripe
        try {
            $sessionId = $stripeService->createOrderCheckoutSession(
                $items,
                (string)$commande->getId(),
                $user->getEmail()
            );

            return $this->json(['sessionId' => $sessionId]);
        } catch (\Exception $e) {
            // Log l'erreur complète
            error_log('Stripe Error: ' . $e->getMessage());
            error_log('Stripe Error Trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'error' => 'Erreur lors de la création du paiement: ' . $e->getMessage(),
                'details' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/payment-success', name: 'payment_success')]
    public function paymentSuccess(Request $request, StripeService $stripeService, CommandeRepository $commandeRepo, PanierService $panierService, EntityManagerInterface $em): Response
    {
        $sessionId = $request->query->get('session_id');
        $orderId = $request->query->get('order_id');

        if (!$sessionId || !$orderId) {
            $this->addFlash('error', 'Données de paiement manquantes');
            return $this->redirectToRoute('panier_index');
        }

        try {
            // Vérifier le statut du paiement
            $paymentStatus = $stripeService->getPaymentStatus($sessionId);

            if ($paymentStatus === 'paid') {
                // Mettre à jour la commande
                $commande = $commandeRepo->find($orderId);
                if ($commande) {
                    $commande->setStatut('validee');
                    $em->flush();

                    // Vider le panier de l'utilisateur
                    $user = $this->getUser();
                    if ($user) {
                        $panier = $panierService->getPanierOrCreate($user);
                        $panierService->clearCart($panier);
                    }

                    $this->addFlash('success', 'Paiement reçu ! Votre commande a été confirmée.');
                    return $this->redirectToRoute('commande_show', ['id' => $orderId]);
                }
            }

            $this->addFlash('error', 'Le paiement n\'a pas été complété');
            return $this->redirectToRoute('panier_index');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la vérification du paiement: ' . $e->getMessage());
            return $this->redirectToRoute('panier_index');
        }
    }

    #[Route('/payment-cancel', name: 'payment_cancel')]
    public function paymentCancel(Request $request): Response
    {
        $this->addFlash('info', 'Paiement annulé');
        return $this->redirectToRoute('panier_index');
    }
    
    // Redirection pour les anciennes URLs Stripe (compatibilité)
    #[Route('/commande/payment-success', name: 'legacy_payment_success')]
    public function legacyPaymentSuccess(Request $request): Response
    {
        // Rediriger vers la nouvelle URL avec les mêmes paramètres
        return $this->redirectToRoute('stripe_commande_payment_success', [
            'session_id' => $request->query->get('session_id'),
            'order_id' => $request->query->get('order_id'),
        ]);
    }

    #[Route('/checkout-page', name: 'checkout_page', methods: ['GET'])]
    public function checkoutPage(Request $request, StripeService $stripeService): Response
    {
        return $this->render('commande/stripe_checkout.html.twig', [
            'stripe_public_key' => $stripeService->getPublicKey(),
        ]);
    }
}
