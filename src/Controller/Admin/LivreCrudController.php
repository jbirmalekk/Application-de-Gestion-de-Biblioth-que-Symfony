<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use App\Repository\UserRepository;
use App\Repository\WishlistRepository;
use App\Service\NotificationService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class LivreCrudController extends AbstractCrudController
{
    public function __construct(
        private WishlistRepository $wishlistRepository,
        private NotificationService $notificationService,
        private UserRepository $userRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Livre::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Livres')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du Livre')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le Livre')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un Livre');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('titre'),
            IntegerField::new('qte'),
            IntegerField::new('isbn'),
            MoneyField::new('prix')->setCurrency('TND'),
            DateField::new('datapub'),
            BooleanField::new('isPremium', 'Premium'),
            TextareaField::new('description')->hideOnIndex(),
            ImageField::new('image')
                ->setBasePath('assets/img/books')
                ->setUploadDir('public/assets/img/books')
                ->setUploadedFileNamePattern('[randomHash].[extension]')
                ->setRequired(false),
            TextEditorField::new('description')->onlyOnDetail(),
            AssociationField::new('editeur'),
            AssociationField::new('categorie'),
            CollectionField::new('auteurs')->onlyOnDetail(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Action::INDEX, Action::DETAIL);
    }

    public function persistEntity($entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Livre) {
            // Notifier tous les utilisateurs qu'un nouveau livre est ajouté
            $users = $this->userRepository->findAll();
            foreach ($users as $user) {
                $this->notificationService->createForUser(
                    $user,
                    sprintf('Un nouveau livre "%s" a été ajouté au catalogue.', $entityInstance->getTitre()),
                    'new_book',
                    $entityInstance
                );
            }
        }
    }

    public function updateEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Livre) {
            // Get original entity data before the update
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changeset = $unitOfWork->getEntityChangeSet($entityInstance);
            
            if (isset($changeset['qte'])) {
                [$oldQte, $newQte] = $changeset['qte'];
                
                error_log(sprintf('[EasyAdmin] Stock change detected: %s - Old: %d, New: %d', 
                    $entityInstance->getTitre(), 
                    $oldQte, 
                    $newQte
                ));
                
                // Check for stock changes and create notifications
                if ($oldQte > 0 && $newQte === 0) {
                    error_log(sprintf('[EasyAdmin] Notifying out of stock for: %s', $entityInstance->getTitre()));
                    $this->notifyOutOfStock($entityInstance);
                } elseif ($oldQte === 0 && $newQte > 0) {
                    error_log(sprintf('[EasyAdmin] Notifying back in stock for: %s', $entityInstance->getTitre()));
                    $this->notifyBackInStock($entityInstance);
                }
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function notifyOutOfStock(Livre $livre): void
    {
        $wishlistItems = $this->wishlistRepository->findByLivre($livre);
        foreach ($wishlistItems as $item) {
            $user = $item->getUser();
            $this->notificationService->createForUser(
                $user,
                sprintf('Le livre "%s" est malheureusement en rupture de stock.', $livre->getTitre()),
                'stock_out',
                $livre
            );
            error_log(sprintf('[EasyAdmin] Out of stock notification created for user: %s', $user->getEmail()));
        }
    }

    private function notifyBackInStock(Livre $livre): void
    {
        $wishlistItems = $this->wishlistRepository->findByLivre($livre);
        foreach ($wishlistItems as $item) {
            if ($item->isNotifyWhenAvailable()) {
                $user = $item->getUser();
                $this->notificationService->createForUser(
                    $user,
                    sprintf('Bonne nouvelle! Le livre "%s" est de nouveau en stock.', $livre->getTitre()),
                    'wishlist_restock',
                    $livre
                );
                error_log(sprintf('[EasyAdmin] Back in stock notification created for user: %s', $user->getEmail()));
            }
        }
    }
}

