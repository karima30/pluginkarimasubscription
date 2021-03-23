<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Extension;

use Ksante\SubscriptionPlugin\Form\Type\ProgramType;
use Ksante\SubscriptionPlugin\Form\Type\ProductSelectedProgramsType;
use Ksante\SubscriptionPlugin\Form\EventListener\CustomCodeFormSubscriber;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CustomCodeFormSubscriber());

        $builder
            ->add('nutritionIndex', ChoiceType::class, [
                'choices'  => [
                    'A' => 'A',
                    'B' => 'B',
                    'C' => 'C',
                    'D' => 'D',
                    'E' => 'E',
                ],
                'label' => 'ksante_subscription.ui.nutritionIndex',
            ])
        ;


        $builder
            ->add('program', ProgramType::class)
        ;

        $builder
            ->add('productSelectedPrograms', ProductSelectedProgramsType::class)
        ;
    }

    /**
     * @inheritdoc
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }

}
