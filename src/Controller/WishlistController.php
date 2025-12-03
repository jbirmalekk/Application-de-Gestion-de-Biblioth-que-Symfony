<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Entity\Wishlist;
use App\Repository\WishlistRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wishlist', name: 'wishlist_')]
#[IsGranted('ROLE_USER')]
class WishlistController extends AbstractController
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $wishlistItems = $this->wishlistRepository->findByUser($user);

        // Filtres
        $filter = $request->query->get('filter', 'all'); // all, available, out_of_stock
        $sort = $request->query->get('sort', 'date_desc'); // date_desc, date_asc, price_asc, price_desc, title_asc

        // Appliquer les filtres
        $filteredItems = $wishlistItems;
        if ($filter === 'available') {
            $filteredItems = array_filter($filteredItems, fn($item) => $item->getLivre()->getQte() > 0);
        } elseif ($filter === 'out_of_stock') {
            $filteredItems = array_filter($filteredItems, fn($item) => $item->getLivre()->getQte() <= 0);
        }

        // Appliquer le tri
        usort($filteredItems, function($a, $b) use ($sort) {
            $livreA = $a->getLivre();
            $livreB = $b->getLivre();
            
            return match($sort) {
                'date_asc' => $a->getAddedAt() <=> $b->getAddedAt(),
                'date_desc' => $b->getAddedAt() <=> $a->getAddedAt(),
                'price_asc' => $livreA->getPrix() <=> $livreB->getPrix(),
                'price_desc' => $livreB->getPrix() <=> $livreA->getPrix(),
                'title_asc' => strcmp($livreA->getTitre(), $livreB->getTitre()),
                default => $b->getAddedAt() <=> $a->getAddedAt(),
            };
        });

        // Calculer les statistiques
        $stats = [
            'total' => count($wishlistItems),
            'available' => count(array_filter($wishlistItems, fn($item) => $item->getLivre()->getQte() > 0)),
            'out_of_stock' => count(array_filter($wishlistItems, fn($item) => $item->getLivre()->getQte() <= 0)),
            'total_value' => array_sum(array_map(fn($item) => $item->getLivre()->getPrix() ?? 0, $wishlistItems)),
            'available_value' => array_sum(array_map(fn($item) => $item->getLivre()->getPrix() ?? 0, 
                array_filter($wishlistItems, fn($item) => $item->getLivre()->getQte() > 0))),
        ];

        return $this->render('wishlist/index.html.twig', [
            'wishlistItems' => array_values($filteredItems),
            'allItems' => $wishlistItems,
            'count' => count($wishlistItems),
            'filter' => $filter,
            'sort' => $sort,
            'stats' => $stats,
        ]);
    }

    #[Route('/add/{id}', name: 'add', methods: ['POST'])]
    public function add(Livre $livre, Request $request): Response
    {
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('wishlist-add-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $user = $this->getUser();
        $existingWishlist = $this->wishlistRepository->findByUserAndLivre($user, $livre);

        if ($existingWishlist) {
            $this->addFlash('info', 'Ce livre est déjà dans votre wishlist');
        } else {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlist->setLivre($livre);
            $wishlist->setNotifyWhenAvailable(true);

            $this->entityManager->persist($wishlist);
            $this->entityManager->flush();

            // Notification in-app pour l'ajout dans la wishlist
            $this->notificationService->createForUser(
                $user,
                sprintf('Le livre "%s" a été ajouté à votre wishlist.', $livre->getTitre()),
                'wishlist_add',
                $livre
            );

            $this->addFlash('success', 'Livre ajouté à votre wishlist');
        }

        return $this->redirectToRoute('wishlist_index');
    }

    #[Route('/remove/{id}', name: 'remove', methods: ['POST'])]
    public function remove(Livre $livre, Request $request): Response
    {
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('wishlist-remove-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('wishlist_index');
        }

        $user = $this->getUser();
        $wishlist = $this->wishlistRepository->findByUserAndLivre($user, $livre);

        if (!$wishlist) {
            $this->addFlash('warning', 'Ce livre n\'est pas dans votre wishlist');
        } else {
            $this->entityManager->remove($wishlist);
            $this->entityManager->flush();
            $this->addFlash('success', 'Livre retiré de votre wishlist');
        }

        return $this->redirectToRoute('wishlist_index');
    }

    #[Route('/toggle/{id}', name: 'toggle', methods: ['POST'])]
    public function toggle(Livre $livre, Request $request): Response
    {
        $token = $request->request->get('_token') ?? $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('wishlist-toggle-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        $user = $this->getUser();
        $wishlist = $this->wishlistRepository->findByUserAndLivre($user, $livre);

        if ($wishlist) {
            $this->entityManager->remove($wishlist);
            $this->addFlash('success', 'Livre retiré de votre wishlist');
        } else {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlist->setLivre($livre);
            $wishlist->setNotifyWhenAvailable(true);
            $this->entityManager->persist($wishlist);

            // Notification in-app pour l'ajout via toggle
            $this->notificationService->createForUser(
                $user,
                sprintf('Le livre "%s" a été ajouté à votre wishlist.', $livre->getTitre()),
                'wishlist_add',
                $livre
            );

            $this->addFlash('success', 'Livre ajouté à votre wishlist');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('wishlist_index');
    }

    #[Route('/check/{id}', name: 'check', methods: ['GET'])]
    public function check(Livre $livre): JsonResponse
    {
        $user = $this->getUser();
        $wishlist = $this->wishlistRepository->findByUserAndLivre($user, $livre);

        return $this->json([
            'inWishlist' => $wishlist !== null,
        ]);
    }

    #[Route('/toggle-notification/{id}', name: 'toggle_notification', methods: ['POST'])]
    public function toggleNotification(Livre $livre, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('wishlist-notification-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('wishlist_index');
        }

        $user = $this->getUser();
        $wishlist = $this->wishlistRepository->findByUserAndLivre($user, $livre);

        if ($wishlist) {
            $wishlist->setNotifyWhenAvailable(!$wishlist->isNotifyWhenAvailable());
            $this->entityManager->flush();
            $this->addFlash('success', $wishlist->isNotifyWhenAvailable() 
                ? 'Notification activée pour ce livre' 
                : 'Notification désactivée pour ce livre');
        }

        return $this->redirectToRoute('wishlist_index');
    }

    #[Route('/add-to-cart/{id}', name: 'add_to_cart', methods: ['POST'])]
    public function addToCart(Livre $livre, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('wishlist-cart-' . $livre->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('wishlist_index');
        }

        // Rediriger vers le contrôleur panier
        return $this->redirectToRoute('panier_ajouter', ['id' => $livre->getId()]);
    }
}
