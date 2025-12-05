<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditeurRepository;
use App\Repository\AuteurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

final class AcceuilController extends AbstractController
{
    #[Route('/', name: 'app_acceuil')]
    public function index(
        LivreRepository $livreRepository,
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository,
        AuteurRepository $auteurRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        // Récupérer les paramètres de recherche et filtres
        $search = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie') ? (int)$request->query->get('categorie') : null;
        $auteurId = $request->query->get('auteur') ? (int)$request->query->get('auteur') : null;
        $editeurId = $request->query->get('editeur') ? (int)$request->query->get('editeur') : null;
        $prixMin = $request->query->get('prix_min') ? (float)$request->query->get('prix_min') : null;
        $prixMax = $request->query->get('prix_max') ? (float)$request->query->get('prix_max') : null;
        $sortBy = $request->query->get('sort_by', 'recent');

        // Récupérer les livres avec filtres
        $featuredBooksQuery = $livreRepository->searchWithFiltersQuery(
            $search ?: null,
            $categorieId,
            $auteurId,
            $editeurId,
            $prixMin,
            $prixMax,
            $sortBy
        );
        
        // Paginer les résultats
        $featuredBooks = $paginator->paginate(
            $featuredBooksQuery,
            $request->query->getInt('page', 1),
            12 // Nombre de livres par page
        );
        
        // Toutes les catégories
        $categories = $categorieRepository->findAll();
        
        // Tous les auteurs
        $auteurs = $auteurRepository->findAll();
        
        // Tous les éditeurs - avec gestion d'erreur
        try {
            $editeurs = $editeurRepository->findAll();
        } catch (\Exception $e) {
            // En cas d'erreur, on utilise un tableau vide
            $editeurs = [];
        }

        return $this->render('acceuil/index.html.twig', [
            'featuredBooks' => $featuredBooks,
            'categories' => $categories,
            'auteurs' => $auteurs,
            'editeurs' => $editeurs,
            'search' => $search,
            'selectedCategorie' => $categorieId,
            'selectedAuteur' => $auteurId,
            'selectedEditeur' => $editeurId,
            'prixMin' => $prixMin,
            'prixMax' => $prixMax,
            'sortBy' => $sortBy,
        ]);
    }
}