<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Ksante\SubscriptionPlugin\Form\Transformer\CustomProductTransformer;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


final class ProgramDetailsType extends AbstractResourceType implements ContainerAwareInterface
{
    /**
     * {@inheritdoc}
     */
    protected $container;
    private $producClassName;
    private $productRepository;

    public function setContainer(ContainerInterface $container = null, $producClassName = null)
    {
        $this->producClassName = $producClassName;
        $this->productRepository = $container->get('sylius.repository.product');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('quantity', NumberType::class, [
                'label' => 'ksante_subscription.ui.quantity',
                'required' => false,
            ])
            ->add('step', NumberType::class, [
                'label' => 'ksante_subscription.ui.step',
                'required' => false,
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
            ->add('product', CustomProductAutoCompleteChoiceType::class, [
                'label' => 'sylius.ui.product',
                'multiple' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
        ;
        $builder->get('product')->addModelTransformer(
            new ReversedTransformer(
                new CustomProductTransformer($this->productRepository, 'code')
            )
        )->addModelTransformer(
            new CustomProductTransformer($this->productRepository, 'code')
        );
    }
}
