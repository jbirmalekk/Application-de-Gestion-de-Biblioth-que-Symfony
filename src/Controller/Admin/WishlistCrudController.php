<?php

namespace App\Controller\Admin;

use App\Entity\Wishlist;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class WishlistCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Wishlist::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            
            AssociationField::new('user')
                ->setLabel('Utilisateur')
                ->formatValue(static fn($value) => $value?->getEmail() ?? 'N/A'),
            
            AssociationField::new('livre')
                ->setLabel('Livre')
                ->formatValue(static fn($value) => $value?->getTitre() ?? 'N/A'),
            
            DateTimeField::new('addedAt')
                ->setLabel('Date d\'ajout')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
            
            BooleanField::new('notifyWhenAvailable')
                ->setLabel('Notifier quand disponible'),
        ];
    }
}
