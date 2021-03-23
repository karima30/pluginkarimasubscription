<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Ksante\SubscriptionPlugin\Repository\ProductRepository;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;


final class ProductSelectedProgramsType extends AbstractResourceType
{
    private $producClassName;

    public function setProductClass($producClassName = null)
    {
        $this->producClassName = $producClassName;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder
            ->add('programs', EntityType::class, array(
                'class' => $this->producClassName,
                'query_builder' => function (ProductRepository $er) {
                    return $er->getPrograms(true);
                },
                'choice_label' => 'name',
                'label'        => 'Product',
                'expanded'     => true,
                'multiple'     => true,
            ));
    }

}
