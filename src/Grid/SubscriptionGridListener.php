<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class SubscriptionGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $subscriptionNameField = Field::fromNameAndType('program', 'string');
        $subscriptionNameField->setLabel('ksante_subscription.ui.program');
        $subscriptionNameField->setPosition(1);

        $grid->addField($subscriptionNameField);
    }
}
