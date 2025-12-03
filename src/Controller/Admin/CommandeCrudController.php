<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            
            AssociationField::new('user')
                ->setLabel('Utilisateur')
                ->formatValue(function ($value) {
                    return $value?->getEmail() ?? 'N/A';
                }),
            
            MoneyField::new('total')
                ->setLabel('Total')
                ->setCurrency('EUR')
                ->setNumDecimals(2),
            
            ChoiceField::new('statut')
                ->setLabel('Statut')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Confirmée' => 'confirmee',
                    'Expédiée' => 'expediee',
                    'Livrée' => 'livree',
                    'Annulée' => 'annulee',
                ]),
            
            DateTimeField::new('createdAt')
                ->setLabel('Date de création')
                ->hideOnForm(),
            
            DateTimeField::new('updatedAt')
                ->setLabel('Date de modification')
                ->hideOnForm(),
            
            AssociationField::new('items')
                ->setLabel('Articles')
                ->onlyOnDetail(),
        ];
    }
}
