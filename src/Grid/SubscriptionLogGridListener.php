<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class SubscriptionLogGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $subscriptionNameField = Field::fromNameAndType('subscription', 'twig');
        $subscriptionNameField->setLabel('ksante_subscription.ui.subscription');
        $subscriptionNameField->setOptions(['template' => '@KsanteSubscriptionPlugin/Admin/Subscription/Subscription/Field/number.html.twig']);
        $subscriptionNameField->setPosition(1);

        $grid->addField($subscriptionNameField);

        $isUpdatedByAdminField = Field::fromNameAndType('isUpdatedByCustomer', 'twig');
        $isUpdatedByAdminField->setLabel('ksante_subscription.ui.isUpdatedByCustomer');
        $isUpdatedByAdminField->setOptions(['template' => '@SyliusUi/Grid/Field/yesNo.html.twig']);

        $grid->addField($isUpdatedByAdminField);

        $isStabilizationField = Field::fromNameAndType('subscription.program.product.program.isStabilizationProgram', 'twig');
        $isStabilizationField->setLabel('ksante_subscription.ui.is_stabilization_program');
        $isStabilizationField->setOptions(['template' => '@SyliusUi/Grid/Field/yesNo.html.twig']);

        $grid->addField($isStabilizationField);
    }
}
