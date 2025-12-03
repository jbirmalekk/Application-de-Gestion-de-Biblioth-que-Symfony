<?php

namespace App\Repository;

use App\Entity\Wishlist;
use App\Entity\User;
use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wishlist>
 *
 * @method Wishlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wishlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wishlist[]    findAll()
 * @method Wishlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WishlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wishlist::class);
    }

    /**
     * Find all wishlist items for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a book is in user's wishlist
     */
    public function findByUserAndLivre(User $user, Livre $livre): ?Wishlist
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.livre = :livre')
            ->setParameter('user', $user)
            ->setParameter('livre', $livre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count wishlist items for a user
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find all books with notification enabled where stock > 0
     */
    public function findNotifiableWithAvailableStock(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.notifyWhenAvailable = true')
            ->innerJoin('w.livre', 'l')
            ->andWhere('l.qte > 0')
            ->orderBy('w.addedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all wishlist items for a book
     */
    public function findByLivre(Livre $livre): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.livre = :livre')
            ->setParameter('livre', $livre)
            ->getQuery()
            ->getResult();
    }
}
