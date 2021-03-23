<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Extension;

use Ksante\SubscriptionPlugin\Form\Type\NutritionalInformationImageType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Bundle\ProductBundle\Form\Type\ProductTranslationType;

final class ProductTranslationTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ingredients', TextareaType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.ingredients',
            ])
            ->add('nutritionalInformationImage', NutritionalInformationImageType::class, [
                'label' => 'ksante_subscription.ui.nutritionalInfo',
                'required' => false,
            ])
            ->add('recipe', TextareaType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.recipe',
            ])
            ->add('producerInfo', TextareaType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.producerInfo',
            ])
            ->add('preparationTips', TextareaType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.preparationTips',
            ])
            ->add('conservationTip', TextType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.conservationTip',
            ])
            ->add('usageTips', TextareaType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.usageTips',
            ])
            ->add('conditioning', TextType::class, [
                'required' => false,
                'label' => 'ksante_subscription.ui.conditioning',
            ])
        ;

    }
    /**
     * @inheritdoc
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductTranslationType::class];
    }

}
