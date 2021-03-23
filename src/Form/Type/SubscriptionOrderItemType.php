<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Ksante\SubscriptionPlugin\Form\Transformer\CustomVariantTransformer;
use Ksante\SubscriptionPlugin\Repository\ProductVariantRepository;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;

class SubscriptionOrderItemType extends AbstractResourceType
{
    /**
     * {@inheritdoc}
     */
    protected $container;

    private $productVariantClass;
    private $taxonClass;
    private $productVariantRepository;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->productVariantRepository = $container->get('sylius.repository.product_variant');
    }

    public function setClasses($productVariantClass, $taxonClass) {
        $this->productVariantClass = $productVariantClass;
        $this->taxonClass = $taxonClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('quantity', NumberType::class, [
                'label' => 'ksante_subscription.ui.quantity',
                'required' => false,
            ])
            ->add('variant', CustomVariantAutoCompleteChoiceType::class, [
                'label' => 'sylius.ui.variant',
                'multiple' => false,
                'required' => true,
            ])
            ->add('taxon', CustomTaxonAutocompleteChoiceType::class, [
                'label' => 'sylius.ui.taxonomy',
                'required' => true,
            ])
        ;
        $builder->get('variant')->addModelTransformer(
            new ReversedTransformer(
                new CustomVariantTransformer($this->productVariantRepository, 'code')
            )
        )->addModelTransformer(
            new CustomVariantTransformer($this->productVariantRepository, 'code')
        );
    }

}

