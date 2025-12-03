<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Form\CheckoutAddressType;
use App\Repository\CommandeRepository;
use App\Service\CouponService;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande', name: 'commande_')]
#[IsGranted('ROLE_USER')]
final class CommandeController extends AbstractController
{
    public function __construct(
        private PanierService $panierService,
        private CommandeRepository $commandeRepository,
        private EntityManagerInterface $entityManager,
        private CouponService $couponService,
    ) {
    }

    #[Route('/checkout', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request): Response
    {
        $user = $this->getUser();
        $panier = $this->panierService->getPanierOrCreate($user);

        if ($panier->getItems()->count() === 0) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_index');
        }

        $form = $this->createForm(CheckoutAddressType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $commande = new Commande();
            $commande->setUser($user);
            $commande->setStatut('en_attente');
            $commande->setAdresseLivraison($data['adresseLivraison']);
            $commande->setCodePostal($data['codePostal']);
            $commande->setVille($data['ville']);
            $commande->setPays($data['pays']);
            $commande->setMethodePaiement($data['methodePaiement']);

            $total = 0.0;
            foreach ($panier->getItems() as $panierItem) {
                $item = new CommandeItem();
                $item->setCommande($commande);
                $item->setLivre($panierItem->getLivre());
                $item->setQuantite($panierItem->getQuantite());
                $item->setPrixUnitaire($panierItem->getPrixUnitaire());

                $commande->addItem($item);
                $total += $item->getSubTotal();
            }

            // Calculer le total avec le coupon si présent
            $promoCode = $request->getSession()->get('cart_promo_code');
            $cartSummary = $this->panierService->getCartSummary($panier, $promoCode);
            $commande->setTotal($cartSummary['total']);

            // Incrémenter l'usage du coupon si un code promo a été utilisé
            if ($promoCode && isset($cartSummary['coupon']) && $cartSummary['coupon']) {
                $this->couponService->incrementerUsage($cartSummary['coupon']);
            }

            // Retirer le code promo de la session après utilisation
            if ($promoCode) {
                $request->getSession()->remove('cart_promo_code');
            }

            $this->entityManager->persist($commande);
            $this->entityManager->flush();

            // Vider le panier après création de la commande
            $this->panierService->clearCart($panier);

            $this->addFlash('success', 'Commande créée avec succès.');

            return $this->redirectToRoute('commande_show', ['id' => $commande->getId()]);
        }

        // Calculer le résumé du panier pour l'affichage
        $cartSummary = $this->panierService->getCartSummary($panier, $request->getSession()->get('cart_promo_code'));

        return $this->render('commande/checkout.html.twig', [
            'form' => $form,
            'panier' => $panier,
            'cartSummary' => $cartSummary,
        ]);
    }

    #[Route('/creer', name: 'creer', methods: ['POST'])]
    public function creerDepuisPanier(Request $request): Response
    {
        // Redirection vers checkout pour remplir l'adresse
        return $this->redirectToRoute('commande_checkout');
    }

    #[Route('/mes-commandes', name: 'mes_commandes', methods: ['GET'])]
    public function mesCommandes(): Response
    {
        $user = $this->getUser();
        $commandes = $this->commandeRepository->findByUser($user);

        return $this->render('commande/mes_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN') && $this->denyAccessUnlessGranted('ROLE_USER');

        if ($commande->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('commande/show.html.twig', [
            'commande' => $commande,
        ]);
    }
}


