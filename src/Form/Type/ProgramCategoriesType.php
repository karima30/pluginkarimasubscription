<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ProgramCategoriesType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('taxon', CustomTaxonAutocompleteChoiceType::class, [
                'label' => 'sylius.form.channel.menu_taxon',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('isObligatory', ChoiceType::class, [
                'choices'  => [
                    'sylius.ui.yes_label' => 1,
                    'sylius.ui.no_label' => 0,
                ],
                'label' => 'ksante_subscription.ui.isObligatory',
            ])
            ->add('priority', NumberType::class, [
                'label' => 'sylius.ui.priority',
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 0,
                        'max' => 4,
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('minimumNumberOfProducts', NumberType::class, [
                'label' => 'ksante_subscription.ui.minimumNumberOfProducts',
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 10000,
                        'groups' => ['sylius'],
                    ]),
                    new NotBlank([
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('maximumNumberOfProducts', NumberType::class, [
                'label' => 'ksante_subscription.ui.maximumNumberOfProducts',
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 10000,
                        'groups' => ['sylius'],
                    ]),
                    new NotBlank([
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
        ;
    }
}
