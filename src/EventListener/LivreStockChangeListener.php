<?php

namespace App\EventListener;

use App\Entity\Livre;
use App\Repository\WishlistRepository;
use App\Service\NotificationService;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class LivreStockChangeListener implements EventSubscriber
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private NotificationService $notificationService,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
            Events::postUpdate,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        error_log('[LivreStockChangeListener] preUpdate triggered');
        $this->detectStockChange($args->getEntity(), $args);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        error_log('[LivreStockChangeListener] postUpdate triggered');
        $this->detectStockChange($args->getEntity(), $args);
    }

    private function detectStockChange(mixed $entity, PreUpdateEventArgs|PostUpdateEventArgs $args): void
    {
        if (!$entity instanceof Livre) {
            error_log('[LivreStockChangeListener] Not a Livre entity, returning');
            return;
        }

        error_log(sprintf('[LivreStockChangeListener] Processing Livre: %s', $entity->getTitre()));

        // Get the change set
        if ($args instanceof PreUpdateEventArgs) {
            $changeset = $args->getEntityChangeSet();
        } else {
            // For postUpdate, we need to get the unit of work
            $changeset = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        }

        error_log(sprintf('[LivreStockChangeListener] Changeset: %s', json_encode($changeset)));

        if (!isset($changeset['qte'])) {
            error_log('[LivreStockChangeListener] No qte change');
            return;
        }

        [$oldQte, $newQte] = $changeset['qte'];

        error_log(sprintf('[LivreStockChangeListener] Stock changed: %d -> %d', $oldQte, $newQte));

        // Case 1: Stock went from > 0 to 0 (out of stock)
        if ($oldQte > 0 && $newQte === 0) {
            error_log(sprintf('[LivreStockChangeListener] Notifying out of stock for %s', $entity->getTitre()));
            $this->notifyOutOfStock($entity);
        }

        // Case 2: Stock went from 0 to > 0 (back in stock)
        if ($oldQte === 0 && $newQte > 0) {
            error_log(sprintf('[LivreStockChangeListener] Notifying back in stock for %s', $entity->getTitre()));
            $this->notifyBackInStock($entity);
        }
    }

    private function notifyOutOfStock(Livre $livre): void
    {
        // Find all users who have this book in wishlist
        $wishlistItems = $this->wishlistRepository->findByLivre($livre);

        foreach ($wishlistItems as $item) {
            $user = $item->getUser();
            $this->notificationService->createForUser(
                $user,
                sprintf('Le livre "%s" est malheureusement en rupture de stock.', $livre->getTitre()),
                'stock_out',
                $livre
            );
            error_log(sprintf('[LivreStockChangeListener] Notification created for user: %s', $user->getEmail()));
        }
    }

    private function notifyBackInStock(Livre $livre): void
    {
        // Find all users who have this book in wishlist with notification enabled
        $wishlistItems = $this->wishlistRepository->findByLivre($livre);

        foreach ($wishlistItems as $item) {
            if ($item->isNotifyWhenAvailable()) {
                $user = $item->getUser();
                $this->notificationService->createForUser(
                    $user,
                    sprintf('Bonne nouvelle! Le livre "%s" est de nouveau en stock.', $livre->getTitre()),
                    'wishlist_restock',
                    $livre
                );
                error_log(sprintf('[LivreStockChangeListener] Back in stock notification created for user: %s', $user->getEmail()));
            }
        }
    }
}

