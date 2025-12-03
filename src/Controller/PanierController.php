<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\PanierItem;
use App\Repository\LivreRepository;
use App\Repository\PanierItemRepository;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier', name: 'panier_')]
#[IsGranted('ROLE_USER')]
final class PanierController extends AbstractController
{
    public function __construct(
        private PanierService $panierService,
        private LivreRepository $livreRepository,
        private PanierItemRepository $panierItemRepository,
    ) {
    }

    /**
     * Afficher le panier
     */
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $panier = $this->panierService->getPanierOrCreate($user);
        $session = $request->getSession();

        // Récupération / mise à jour du code promo saisi
        if ($request->isMethod('POST') && $request->request->has('promo_code')) {
            $promoCodeInput = trim($request->request->get('promo_code', ''));
            if (!empty($promoCodeInput)) {
                $session->set('cart_promo_code', $promoCodeInput);
            } else {
                $session->remove('cart_promo_code');
            }
            
            // Rediriger pour éviter le re-POST et afficher les résultats
            return $this->redirectToRoute('panier_index');
        }

        $promoCode = $session->get('cart_promo_code');
        $cartSummary = $this->panierService->getCartSummary($panier, $promoCode);

        // Messages éventuels liés au code promo (après redirection)
        if ($promoCode && $cartSummary['promoError']) {
            $this->addFlash('error', $cartSummary['promoError']);
        } elseif ($promoCode && $cartSummary['promoCode'] && !$cartSummary['promoError']) {
            $this->addFlash('success', $cartSummary['promoDescription'] ?? 'Code promo appliqué avec succès.');
        }

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
            'cartSummary' => $cartSummary,
        ]);
    }

    /**
     * Ajouter un livre au panier avec sélection de quantité
     */
    #[Route('/ajouter/{id}', name: 'ajouter', methods: ['GET', 'POST'])]
    public function ajouter(
        Livre $livre,
        Request $request,
    ): Response {
        // Si GET, afficher la page de sélection de quantité (optionnel)
        if ($request->isMethod('GET')) {
            return $this->render('panier/add_to_cart.html.twig', [
                'livre' => $livre,
            ]);
        }

        // Si POST, ajouter au panier
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le token CSRF
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('panier-add-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $quantite = max(1, (int) $request->get('quantite', 1));

        try {
            $panier = $this->panierService->getPanierOrCreate($user);
            $this->panierService->addToCart($panier, $livre, $quantite);
            $this->addFlash('success', sprintf('%s a été ajouté au panier', $livre->getTitre()));
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->redirectToRoute('panier_index');
    }

    /**
     * Mettre à jour la quantité d'un article
     */
    #[Route('/mettre-a-jour/{itemId}', name: 'mettre_a_jour', methods: ['POST'])]
    public function mettreAJour(
        int $itemId,
        Request $request,
    ): Response {
        $item = $this->panierItemRepository->find($itemId);

        if (!$item || $item->getPanier()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $quantite = max(1, (int) $request->get('quantite', 1));

        try {
            $this->panierService->updateItemQuantity($item, $quantite);
        } catch (\RuntimeException $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            $this->addFlash('error', $e->getMessage());
            return $this->redirect($this->generateUrl('panier_index'));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'subtotal' => $item->getSubTotal(),
                'total' => $item->getPanier()->getTotal(),
            ]);
        }

        $this->addFlash('success', 'Quantité mise à jour');

        return $this->redirect($this->generateUrl('panier_index'));
    }

    /**
     * Supprimer un article du panier
     */
    #[Route('/supprimer/{itemId}', name: 'supprimer', methods: ['POST'])]
    public function supprimer(
        int $itemId,
        Request $request,
    ): Response {
        $item = $this->panierItemRepository->find($itemId);

        if (!$item || $item->getPanier()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $panier = $item->getPanier();

        if (!$this->isCsrfTokenValid('delete_item_' . $item->getId(), $request->get('_token'))) {
            return $this->json(['error' => 'Token invalide'], Response::HTTP_BAD_REQUEST);
        }

        $this->panierService->removeFromCart($panier, $item);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'message' => 'Article supprimé',
                'total' => $panier->getTotal(),
                'itemCount' => count($panier->getItems()),
            ]);
        }

        $this->addFlash('success', 'Article supprimé du panier');

        return $this->redirect($this->generateUrl('panier_index'));
    }

    /**
     * Vider le panier
     */
    #[Route('/vider', name: 'vider', methods: ['POST'])]
    public function vider(Request $request): Response
    {
        $user = $this->getUser();
        $panier = $this->panierService->getPanierOrCreate($user);

        if (!$this->isCsrfTokenValid('clear_panier', $request->get('_token'))) {
            return $this->json(['error' => 'Token invalide'], Response::HTTP_BAD_REQUEST);
        }

        $this->panierService->clearCart($panier);

        $this->addFlash('success', 'Panier vidé');

        return $this->redirect($this->generateUrl('panier_index'));
    }
}
