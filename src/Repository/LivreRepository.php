<?php

namespace App\Repository;

use App\Entity\Livre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Livre>
 */
class LivreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livre::class);
    }

    /**
     * Recherche avancée avec filtres
     */
    public function searchWithFilters(
        ?string $search = null,
        ?int $categorieId = null,
        ?int $auteurId = null,
        ?int $editeurId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        string $sortBy = 'recent'
    ): array
    {
        $qb = $this->createQueryBuilder('l');

        // Recherche par titre
        if ($search) {
            $qb->andWhere('l.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre catégorie
        if ($categorieId) {
            $qb->andWhere('l.categorie = :categorie')
                ->setParameter('categorie', $categorieId);
        }

        // Filtre auteur
        if ($auteurId) {
            $qb->andWhere(':auteur MEMBER OF l.auteurs')
                ->setParameter('auteur', $auteurId);
        }

        // Filtre éditeur
        if ($editeurId) {
            $qb->andWhere('l.editeur = :editeur')
                ->setParameter('editeur', $editeurId);
        }

        // Filtre prix minimum
        if ($prixMin !== null) {
            $qb->andWhere('l.prix >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        // Filtre prix maximum
        if ($prixMax !== null) {
            $qb->andWhere('l.prix <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        // Tri
        switch ($sortBy) {
            case 'price_asc':
                $qb->orderBy('l.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('l.prix', 'DESC');
                break;
            case 'title_asc':
                $qb->orderBy('l.titre', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('l.titre', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('l.datapub', 'DESC')
                    ->addOrderBy('l.id', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche avancée avec filtres - retourne une Query pour pagination
     */
    public function searchWithFiltersQuery(
        ?string $search = null,
        ?int $categorieId = null,
        ?int $auteurId = null,
        ?int $editeurId = null,
        ?float $prixMin = null,
        ?float $prixMax = null,
        string $sortBy = 'recent'
    )
    {
        $qb = $this->createQueryBuilder('l');

        // Recherche par titre
        if ($search) {
            $qb->andWhere('l.titre LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filtre catégorie
        if ($categorieId) {
            $qb->andWhere('l.categorie = :categorie')
                ->setParameter('categorie', $categorieId);
        }

        // Filtre auteur
        if ($auteurId) {
            $qb->andWhere(':auteur MEMBER OF l.auteurs')
                ->setParameter('auteur', $auteurId);
        }

        // Filtre éditeur
        if ($editeurId) {
            $qb->andWhere('l.editeur = :editeur')
                ->setParameter('editeur', $editeurId);
        }

        // Filtre prix minimum
        if ($prixMin !== null) {
            $qb->andWhere('l.prix >= :prixMin')
                ->setParameter('prixMin', $prixMin);
        }

        // Filtre prix maximum
        if ($prixMax !== null) {
            $qb->andWhere('l.prix <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }

        // Tri
        switch ($sortBy) {
            case 'price_asc':
                $qb->orderBy('l.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('l.prix', 'DESC');
                break;
            case 'title_asc':
                $qb->orderBy('l.titre', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('l.titre', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('l.datapub', 'DESC')
                    ->addOrderBy('l.id', 'DESC');
                break;
        }

        return $qb->getQuery();
    }
}
