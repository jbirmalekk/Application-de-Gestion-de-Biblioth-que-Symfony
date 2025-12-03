<?php

namespace App\Service;

use App\Entity\Livre;
use App\Entity\User;
use App\Repository\WishlistRepository;
use Doctrine\ORM\EntityManagerInterface;

class WishlistService
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Check if a book is in user's wishlist
     */
    public function isInWishlist(User $user, Livre $livre): bool
    {
        return $this->wishlistRepository->findByUserAndLivre($user, $livre) !== null;
    }

    /**
     * Get user's wishlist count
     */
    public function getWishlistCount(User $user): int
    {
        return $this->wishlistRepository->countByUser($user);
    }

    /**
     * Get all items in user's wishlist
     */
    public function getUserWishlist(User $user): array
    {
        return $this->wishlistRepository->findByUser($user);
    }

    /**
     * Notify users when a book becomes available
     * This would typically be called via a Symfony command scheduled with cron
     */
    public function notifyAvailability(Livre $livre): void
    {
        // Find all wishlist items for this book with notifications enabled
        $wishlistItems = $this->wishlistRepository->findByLivre($livre);
        
        foreach ($wishlistItems as $item) {
            if ($item->isNotifyWhenAvailable() && $livre->getQte() > 0) {
                // Here you would send an email notification
                // $this->mailer->send(...);
                
                // Disable notification after sending
                $item->setNotifyWhenAvailable(false);
                $this->entityManager->persist($item);
            }
        }
        
        $this->entityManager->flush();
    }

    /**
     * Get wishlist statistics for a user
     */
    public function getWishlistStats(User $user): array
    {
        $wishlist = $this->getUserWishlist($user);
        
        $totalValue = 0;
        $availableValue = 0;
        $outOfStockCount = 0;
        $notificationsEnabled = 0;
        
        foreach ($wishlist as $item) {
            $livre = $item->getLivre();
            $prix = $livre->getPrix() ?? 0;
            $totalValue += $prix;
            
            if ($livre->getQte() > 0) {
                $availableValue += $prix;
            } else {
                $outOfStockCount++;
            }
            
            if ($item->isNotifyWhenAvailable()) {
                $notificationsEnabled++;
            }
        }
        
        return [
            'count' => count($wishlist),
            'totalValue' => $totalValue,
            'availableValue' => $availableValue,
            'outOfStockCount' => $outOfStockCount,
            'availableCount' => count($wishlist) - $outOfStockCount,
            'notificationsEnabled' => $notificationsEnabled,
        ];
    }
}
