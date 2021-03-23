<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraints\Range;


final class ProgramType extends AbstractResourceType implements ContainerAwareInterface
{
    private $container;
    private $producClassName;
    private $productRepository;
    private $productDetailsRepository;

    public function setContainer(ContainerInterface $container = null, $producClassName = null)
    {
        $this->producClassName = $producClassName;
        $this->productRepository = $container->get('sylius.repository.product');
        $this->productDetailsRepository = $container->get('ksante_subscription.repository.program_details');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('periodicity', ChoiceType::class, [
                'choices'  => [
                    'ksante_subscription.ui.7' => '7',
                    'ksante_subscription.ui.28' => '28',
                ],
                'label' => 'ksante_subscription.ui.periodicity',
            ])
            ->add('isStabilizationProgram', CheckboxType::class, [
                'required' => true,
                'label' => 'ksante_subscription.ui.is_stabilization_program',
            ])
            ->add('minimumSubscription', IntegerType::class, [
                'label' => 'ksante_subscription.ui.minimumProgram',
                'required' => true,
                'constraints' => [
                    new Range([
                        'min' => 1,
                        'max' => 10000,
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('maximumSubscription', NumberType::class, [
                'label' => 'ksante_subscription.ui.maximumProgram',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 0,
                        'max' => 10000,
                        'groups' => ['sylius'],
                    ]),
                ],
            ])
            ->add('programCategoriesDetails', CollectionType::class, array(
                'entry_type' => ProgramCategoriesType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'label' => 'sylius.ui.taxons',
                //'prototype' => false,
                'button_add_label' => 'ksante_subscription.ui.addTaxon',
            ))
            ->add('programDetails', CollectionType::class, array(
                'entry_type' => ProgramDetailsType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'label' => 'sylius.ui.products',
                //'prototype' => false,
                'button_add_label' => 'ksante_subscription.ui.add_product',
            ))
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $subscription = $event->getData();

            $form = $event->getForm();

        });
    }

}
