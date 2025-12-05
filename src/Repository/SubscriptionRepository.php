<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    public function findActiveByUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->where('s.user = :user')
            ->andWhere('s.active = true')
            ->andWhere('s.endDate > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.endDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiringSoon(int $days = 7): array
    {
        $now = new \DateTime();
        $future = new \DateTime("+{$days} days");

        return $this->createQueryBuilder('s')
            ->where('s.active = true')
            ->andWhere('s.endDate BETWEEN :now AND :future')
            ->setParameter('now', $now)
            ->setParameter('future', $future)
            ->getQuery()
            ->getResult();
    }
}
