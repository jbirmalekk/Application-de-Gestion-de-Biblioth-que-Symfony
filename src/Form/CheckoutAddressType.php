<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CheckoutAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adresseLivraison', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 123 Rue de la Paix',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre adresse de livraison.',
                    ]),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: 75001',
                    'class' => 'form-control',
                    'maxlength' => 10,
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre code postal.',
                    ]),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: Paris',
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner votre ville.',
                    ]),
                ],
            ])
            ->add('pays', CountryType::class, [
                'label' => 'Pays',
                'required' => true,
                'preferred_choices' => ['FR'],
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner votre pays.',
                    ]),
                ],
            ])
            ->add('methodePaiement', ChoiceType::class, [
                'label' => 'Moyen de paiement',
                'required' => true,
                'choices' => [
                    'Carte bancaire' => 'carte',
                    'PayPal' => 'paypal',
                    'Virement bancaire' => 'virement',
                    'Paiement à domicile' => 'domicile',
                ],
                'placeholder' => 'Sélectionner un moyen de paiement',
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un moyen de paiement.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}

