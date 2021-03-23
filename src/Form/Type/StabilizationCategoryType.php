<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class StabilizationCategoryType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'sylius.ui.name',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'sylius.ui.description',
                'required' => true,
            ])
            ->add('categoryTaxon', CustomTaxonAutocompleteChoiceType::class, [
                'label' => 'sylius.ui.taxonomy',
                'required' => true,
            ])
        ;
    }

}

