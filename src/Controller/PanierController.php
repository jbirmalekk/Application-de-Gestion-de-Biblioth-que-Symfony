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
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $panier = $this->panierService->getPanierOrCreate($user);

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
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
        if ($request->isMethod('POST')) {
            // Vérifier le token CSRF
            if (!$this->isCsrfTokenValid('add_panier_' . $livre->getId(), $request->get('_token'))) {
                return $this->json(['error' => 'Token invalide'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->getUser();
            $quantite = max(1, (int) $request->get('quantite', 1));

            try {
                $panier = $this->panierService->getPanierOrCreate($user);
                $this->panierService->addToCart($panier, $livre, $quantite);
            } catch (\RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('panier_ajouter', ['id' => $livre->getId()]);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Livre ajouté au panier',
                    'cartItemCount' => count($panier->getItems()),
                    'cartTotal' => $panier->getTotal(),
                ]);
            }

            $this->addFlash('success', $livre->getTitre() . ' ajouté au panier');

            // Toujours rediriger vers la page du panier
            return $this->redirectToRoute('panier_index');
        }

        // Afficher la page pour sélectionner la quantité
        return $this->render('panier/add_to_cart.html.twig', [
            'livre' => $livre,
        ]);
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
