<?php

namespace App\Controller\Admin;

use App\Entity\Avis;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class AvisCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Avis::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            
            AssociationField::new('livre')
                ->setLabel('Livre')
                ->formatValue(static fn($value) => $value?->getTitre() ?? 'N/A'),
            
            AssociationField::new('user')
                ->setLabel('Utilisateur')
                ->formatValue(static fn($value) => $value?->getEmail() ?? 'N/A'),
            
            IntegerField::new('note')
                ->setLabel('Note')
                ->setHelp('Note de 1 à 5'),
            
            IntegerField::new('etoiles')
                ->setLabel('Étoiles')
                ->setHelp('Nombre d\'étoiles')
                ->hideOnForm(),
            
            TextareaField::new('texte')
                ->setLabel('Commentaire')
                ->setFormTypeOption('attr', ['rows' => 5]),
            
            BooleanField::new('approuve')
                ->setLabel('Approuvé')
                ->setHelp('Marquer comme approuvé pour afficher aux autres utilisateurs'),
            
            DateTimeField::new('dateCreation')
                ->setLabel('Date de création')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->hideOnForm(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fas fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fas fa-trash');
            });
    }
}
