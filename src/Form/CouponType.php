<?php

namespace App\Form;

use App\Entity\Coupon;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class CouponType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code promo',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex: PROMO10',
                    'class' => 'form-control',
                    'style' => 'text-transform: uppercase;',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez renseigner un code promo.',
                    ]),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de remise',
                'choices' => [
                    'Pourcentage' => Coupon::TYPE_PERCENTAGE,
                    'Montant fixe' => Coupon::TYPE_FIXED,
                    'Livraison gratuite' => Coupon::TYPE_FREE_SHIPPING,
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('valeur', MoneyType::class, [
                'label' => 'Valeur',
                'required' => true,
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Pour un pourcentage, entrez 10 pour 10%. Pour un montant fixe, entrez le montant en €.',
                'constraints' => [
                    new NotBlank(),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'La valeur doit être positive ou nulle.',
                    ]),
                ],
            ])
            ->add('montantMinimum', MoneyType::class, [
                'label' => 'Montant minimum (optionnel)',
                'required' => false,
                'currency' => 'EUR',
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Montant minimum du panier pour que le coupon soit valable.',
            ])
            ->add('dateExpiration', DateTimeType::class, [
                'label' => 'Date d\'expiration (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('usageMax', IntegerType::class, [
                'label' => 'Usage maximum (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                ],
                'help' => 'Nombre maximum d\'utilisations du coupon. Laissez vide pour illimité.',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                ],
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'data' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Coupon::class,
        ]);
    }
}

