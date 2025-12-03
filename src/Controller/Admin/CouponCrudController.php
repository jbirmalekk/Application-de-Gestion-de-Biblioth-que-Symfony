<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            
            TextField::new('code')
                ->setLabel('Code du coupon')
                ->setHelp('Le code doit être unique')
                ->setFormTypeOption('attr', ['placeholder' => 'ex: NOEL2024']),
            
            ChoiceField::new('type')
                ->setLabel('Type de réduction')
                ->setChoices([
                    'Pourcentage (%)' => 'percentage',
                    'Montant fixe (€)' => 'fixed',
                    'Livraison gratuite' => 'free_shipping',
                ]),
            
            NumberField::new('valeur')
                ->setLabel('Valeur')
                ->setHelp('Montant de la réduction ou pourcentage')
                ->setFormTypeOption('attr', ['step' => '0.01']),
            
            NumberField::new('montantMinimum')
                ->setLabel('Montant minimum (€)')
                ->setHelp('Montant minimum d\'achat pour appliquer le coupon')
                ->setFormTypeOption('attr', ['step' => '0.01', 'placeholder' => 'Optionnel']),
            
            DateTimeField::new('dateExpiration')
                ->setLabel('Date d\'expiration')
                ->setHelp('Laissez vide pour pas d\'expiration'),
            
            NumberField::new('usageMax')
                ->setLabel('Nombre d\'utilisations max')
                ->setHelp('Laissez vide pour usage illimité'),
            
            NumberField::new('usageActuel')
                ->setLabel('Utilisations actuelles')
                ->setFormTypeOption('disabled', true),
            
            BooleanField::new('actif')
                ->setLabel('Actif')
                ->setHelp('Cochez pour activer ce coupon'),
            
            TextareaField::new('description')
                ->setLabel('Description')
                ->setHelp('Description du coupon (optionnel)')
                ->setFormTypeOption('attr', ['rows' => 3, 'placeholder' => 'ex: Réduction de Noël']),
            
            DateTimeField::new('createdAt')
                ->setLabel('Date de création')
                ->hideOnForm(),
            
            DateTimeField::new('updatedAt')
                ->setLabel('Date de modification')
                ->hideOnForm(),
        ];
    }
}
