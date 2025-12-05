<?php

namespace App\Service;

use App\Entity\Livre;
use App\Entity\User;
use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionService
{
    private const MONTHLY_PRICE = 9.99;
    private const YEARLY_PRICE = 99.99;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SubscriptionRepository $subscriptionRepository
    ) {}

    public function canAccessPremiumBook(User $user, Livre $livre): bool
    {
        // Si le livre n'est pas premium, tout le monde peut y accéder
        if (!$livre->isPremium()) {
            return true;
        }

        // Vérifier si l'utilisateur a un abonnement actif
        return $user->hasActiveSubscription();
    }

    public function createSubscription(User $user, string $type): Subscription
    {
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setType($type);
        $subscription->setStartDate(new \DateTime());
        
        // Calculer la date de fin
        if ($type === 'monthly') {
            $endDate = new \DateTime('+1 month');
            $subscription->setPrice((string)self::MONTHLY_PRICE);
        } else {
            $endDate = new \DateTime('+1 year');
            $subscription->setPrice((string)self::YEARLY_PRICE);
        }
        
        $subscription->setEndDate($endDate);
        $subscription->setActive(true);
        $subscription->setStatut('active');

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    public function renewSubscription(Subscription $subscription): void
    {
        $startDate = $subscription->getEndDate() > new \DateTime() 
            ? $subscription->getEndDate() 
            : new \DateTime();
        
        $subscription->setStartDate($startDate);
        
        if ($subscription->getType() === 'monthly') {
            $endDate = (clone $startDate)->modify('+1 month');
        } else {
            $endDate = (clone $startDate)->modify('+1 year');
        }
        
        $subscription->setEndDate($endDate);
        $subscription->setActive(true);
        $subscription->setStatut('active');

        $this->entityManager->flush();
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $subscription->setActive(false);
        $subscription->setStatut('cancelled');
        $this->entityManager->flush();
    }

    public function checkAndUpdateExpiredSubscriptions(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $expiredCount = $qb->update(Subscription::class, 's')
            ->set('s.active', ':active')
            ->set('s.statut', ':statut')
            ->where('s.endDate < :now')
            ->andWhere('s.active = true')
            ->setParameter('active', false)
            ->setParameter('statut', 'expired')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();

        return $expiredCount;
    }

    public function getPrice(string $type): float
    {
        return $type === 'monthly' ? self::MONTHLY_PRICE : self::YEARLY_PRICE;
    }

    public function getExpiringSoonSubscriptions(int $days = 7): array
    {
        return $this->subscriptionRepository->findExpiringSoon($days);
    }
}
