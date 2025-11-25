<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use App\Repository\CategorieRepository;
use App\Repository\EditeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AcceuilController extends AbstractController
{
    #[Route('/', name: 'app_acceuil')]
    public function index(
        LivreRepository $livreRepository,
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository
    ): Response
    {
        // Livres récents (3 derniers)
        $featuredBooks = $livreRepository->findBy([], ['id' => 'DESC'], 3);
        
        // Toutes les catégories
        $categories = $categorieRepository->findAll();
        
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
            'editeurs' => $editeurs,
        ]);
    }
}