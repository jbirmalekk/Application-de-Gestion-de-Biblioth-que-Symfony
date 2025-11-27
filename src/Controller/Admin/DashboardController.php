<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Livre;
use App\Entity\Auteur;
use App\Entity\Editeur;
use App\Entity\Categorie;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\AuteurRepository;
use App\Repository\EditeurRepository;
use App\Repository\CategorieRepository;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{

private LivreRepository $livreRepository;
private UserRepository $userRepository;
private AuteurRepository $auteurRepository;
private EditeurRepository $editeurRepository;
private CategorieRepository $categorieRepository;
private EntityManagerInterface $em;

public function __construct(
    LivreRepository $livreRepository,
    UserRepository $userRepository,
    AuteurRepository $auteurRepository,
    EditeurRepository $editeurRepository,
    CategorieRepository $categorieRepository,
    EntityManagerInterface $em
) {
    $this->livreRepository = $livreRepository;
    $this->userRepository = $userRepository;
    $this->auteurRepository = $auteurRepository;
    $this->editeurRepository = $editeurRepository;
    $this->categorieRepository = $categorieRepository;
    $this->em = $em;
}

public function index(): Response
{
    $livreCount = $this->livreRepository->count([]);
    $userCount = $this->userRepository->count([]);
    $auteurCount = $this->auteurRepository->count([]);
    $editeurCount = $this->editeurRepository->count([]);
    $categorieCount = $this->categorieRepository->count([]);

    // Récupérer les statistiques par catégorie pour le graphique
    $categoriesStats = $this->em->createQuery(
        'SELECT c.designation, COUNT(l.id) as count FROM App\Entity\Categorie c LEFT JOIN App\Entity\Livre l WITH l.categorie = c GROUP BY c.id'
    )->getResult();

    $categorieLabels = [];
    $categorieCounts = [];
    foreach ($categoriesStats as $stat) {
        $categorieLabels[] = $stat['designation'];
        $categorieCounts[] = (int)$stat['count'];
    }

    // Livres par date de publication
    $allLivres = $this->em->createQuery(
        'SELECT l.datapub FROM App\Entity\Livre l WHERE l.datapub IS NOT NULL ORDER BY l.datapub'
    )->getResult();

    $dateStatsTemp = [];
    foreach ($allLivres as $livre) {
        $mois = $livre['datapub']->format('Y-m');
        if (!isset($dateStatsTemp[$mois])) {
            $dateStatsTemp[$mois] = 0;
        }
        $dateStatsTemp[$mois]++;
    }

    $dateLabels = [];
    $dateCounts = [];
    foreach ($dateStatsTemp as $mois => $count) {
        $dateLabels[] = $mois;
        $dateCounts[] = $count;
    }

    // Calculer le max pour les barres de progression
    $maxCount = !empty($categorieCounts) ? max($categorieCounts) : 1;

    return $this->render('admin/dashboard.html.twig', [
        'livreCount' => $livreCount,
        'userCount' => $userCount,
        'auteurCount' => $auteurCount,
        'editeurCount' => $editeurCount,
        'categorieCount' => $categorieCount,
        'categorieLabels' => json_encode($categorieLabels),
        'categorieCounts' => json_encode($categorieCounts),
        'dateLabels' => json_encode($dateLabels),
        'dateCounts' => json_encode($dateCounts),
        'maxCount' => $maxCount,
    ]);
}
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bibliothèque');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', \App\Entity\User::class);
        yield MenuItem::linkToCrud('Livres', 'fas fa-book', Livre::class);
        yield MenuItem::linkToCrud('Auteurs', 'fas fa-user', Auteur::class);
        yield MenuItem::linkToCrud('Editeurs', 'fas fa-building', Editeur::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Categorie::class);
        yield MenuItem::section('Administrateur');
        yield MenuItem::linkToRoute('Dashboard Biblio', 'fas fa-home', 'app_acceuil');
        yield MenuItem::linkToLogout('Déconnecter', 'fas fa-sign-out-alt');
    }
}
