<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Récupère tous les avis approuvés pour un livre
     */
    public function findApprouvesByLivre(Livre $livre): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.livre = :livre')
            ->andWhere('a.approuve = :approuve')
            ->setParameter('livre', $livre)
            ->setParameter('approuve', true)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les avis en attente de modération
     */
    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.approuve = :approuve')
            ->setParameter('approuve', false)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d'un livre
     */
    public function getNoteMoyenne(Livre $livre): ?float
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.note) as moyenne')
            ->where('a.livre = :livre')
            ->andWhere('a.approuve = :approuve')
            ->setParameter('livre', $livre)
            ->setParameter('approuve', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Compte le nombre d'avis approuvés pour un livre
     */
    public function countApprouvesByLivre(Livre $livre): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.livre = :livre')
            ->andWhere('a.approuve = :approuve')
            ->setParameter('livre', $livre)
            ->setParameter('approuve', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

