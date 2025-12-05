<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Gestion des Abonnements')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail de l\'Abonnement')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user', 'Utilisateur'),
            ChoiceField::new('type', 'Type')
                ->setChoices([
                    'Mensuel' => 'monthly',
                    'Annuel' => 'yearly'
                ]),
            DateTimeField::new('startDate', 'Date de début'),
            DateTimeField::new('endDate', 'Date de fin'),
            BooleanField::new('active', 'Actif'),
            MoneyField::new('price', 'Prix')->setCurrency('EUR'),
            ChoiceField::new('statut', 'Statut')
                ->setChoices([
                    'Actif' => 'active',
                    'Expiré' => 'expired',
                    'Annulé' => 'cancelled'
                ]),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
        ];
    }
}
