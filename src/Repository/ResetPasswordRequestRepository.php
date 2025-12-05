<?php

namespace App\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 */
class ResetPasswordRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function findValidToken(string $token): ?ResetPasswordRequest
    {
        return $this->createQueryBuilder('r')
            ->where('r.token = :token')
            ->andWhere('r.isUsed = false')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidatePreviousRequests(User $user): void
    {
        $this->createQueryBuilder('r')
            ->update()
            ->set('r.isUsed', 'true')
            ->where('r.user = :user')
            ->andWhere('r.isUsed = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function save(ResetPasswordRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ResetPasswordRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
