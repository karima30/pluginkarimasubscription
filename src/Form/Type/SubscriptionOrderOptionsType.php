<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

final class SubscriptionOrderOptionsType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('optionsItems', CollectionType::class, array(
                'entry_type' => SubscriptionOrderItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'by_reference' => false,
                'label' => 'sylius.ui.products',
                //'prototype' => false,
                'button_add_label' => 'ksante_subscription.ui.add_option',
            ));
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_subscription_order_options';
    }
}
