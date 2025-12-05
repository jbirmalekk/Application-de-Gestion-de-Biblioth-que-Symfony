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
use App\Entity\Commande;
use App\Entity\Coupon;
use App\Entity\Avis;
use App\Entity\Wishlist;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\AuteurRepository;
use App\Repository\EditeurRepository;
use App\Repository\CategorieRepository;
use App\Repository\CommandeRepository;
use App\Repository\CouponRepository;
use App\Repository\AvisRepository;
use App\Repository\WishlistRepository;


#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{

private LivreRepository $livreRepository;
private UserRepository $userRepository;
private AuteurRepository $auteurRepository;
private EditeurRepository $editeurRepository;
private CategorieRepository $categorieRepository;
private CommandeRepository $commandeRepository;
private CouponRepository $couponRepository;
private AvisRepository $avisRepository;
private WishlistRepository $wishlistRepository;
private EntityManagerInterface $em;

public function __construct(
    LivreRepository $livreRepository,
    UserRepository $userRepository,
    AuteurRepository $auteurRepository,
    EditeurRepository $editeurRepository,
    CategorieRepository $categorieRepository,
    CommandeRepository $commandeRepository,
    CouponRepository $couponRepository,
    AvisRepository $avisRepository,
    WishlistRepository $wishlistRepository,
    EntityManagerInterface $em
) {
    $this->livreRepository = $livreRepository;
    $this->userRepository = $userRepository;
    $this->auteurRepository = $auteurRepository;
    $this->editeurRepository = $editeurRepository;
    $this->categorieRepository = $categorieRepository;
    $this->commandeRepository = $commandeRepository;
    $this->couponRepository = $couponRepository;
    $this->avisRepository = $avisRepository;
    $this->wishlistRepository = $wishlistRepository;
    $this->em = $em;
}

public function index(): Response
    {
        $livreCount = $this->livreRepository->count([]);
        $userCount = $this->userRepository->count([]);
        $auteurCount = $this->auteurRepository->count([]);
        $editeurCount = $this->editeurRepository->count([]);
        $categorieCount = $this->categorieRepository->count([]);
        $commandeCount = $this->commandeRepository->count([]);
        $couponCount = $this->couponRepository->count([]);
        $avisCount = $this->avisRepository->count([]);
        $avisEnAttenteCount = count($this->avisRepository->findEnAttente());
        $wishlistCount = $this->wishlistRepository->count([]);
        
        // Compter les livres en stock et hors stock
        $allLivres = $this->livreRepository->findAll();
        $livreEnStockCount = 0;
        $livreHorsStockCount = 0;
        
        foreach ($allLivres as $livre) {
            if ($livre->getQte() > 0) {
                $livreEnStockCount++;
            } else {
                $livreHorsStockCount++;
            }
        }
        
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
        'livreEnStockCount' => $livreEnStockCount,
        'livreHorsStockCount' => $livreHorsStockCount,
        'userCount' => $userCount,
        'auteurCount' => $auteurCount,
        'editeurCount' => $editeurCount,
        'categorieCount' => $categorieCount,
        'commandeCount' => $commandeCount,
        'couponCount' => $couponCount,
        'avisCount' => $avisCount,
        'avisEnAttenteCount' => $avisEnAttenteCount,
        'wishlistCount' => $wishlistCount,
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
        yield MenuItem::section('Gestion des contenus');
        yield MenuItem::linkToCrud('Livres', 'fas fa-book', Livre::class);
        yield MenuItem::linkToCrud('Auteurs', 'fas fa-user', Auteur::class);
        yield MenuItem::linkToCrud('Editeurs', 'fas fa-building', Editeur::class);
        yield MenuItem::linkToCrud('Catégories', 'fas fa-tags', Categorie::class);
        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', \App\Entity\User::class);
        yield MenuItem::section('Gestion des commandes');
        yield MenuItem::linkToCrud('Commandes', 'fas fa-shopping-cart', Commande::class);
        yield MenuItem::section('Gestion des coupons');
        yield MenuItem::linkToCrud('Coupons', 'fas fa-ticket-alt', Coupon::class);
        yield MenuItem::section('Abonnements Premium');
        yield MenuItem::linkToCrud('Abonnements', 'fas fa-star', \App\Entity\Subscription::class);
        yield MenuItem::section('Gestion des avis et favoris');
        yield MenuItem::linkToCrud('Avis', 'fas fa-comments', Avis::class);
        yield MenuItem::linkToCrud('Favoris', 'fas fa-heart', Wishlist::class);
        yield MenuItem::section('Navigation');
        yield MenuItem::linkToRoute('Retour à la bibliothèque', 'fas fa-home', 'app_acceuil');
        yield MenuItem::linkToLogout('Déconnecter', 'fas fa-sign-out-alt');
    }
}
