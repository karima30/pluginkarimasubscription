<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class SubscriptionOrderGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $codeField = Field::fromNameAndType('order', 'twig');
        $codeField->setLabel('sylius.ui.order');
        $codeField->setOptions(['template' => '@SyliusAdmin/Order/Grid/Field/number.html.twig']);
        $codeField->setPosition(1);
        $grid->addField($codeField);
    }
}
