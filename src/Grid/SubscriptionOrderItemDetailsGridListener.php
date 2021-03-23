<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class SubscriptionOrderItemDetailsGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $codeField = Field::fromNameAndType('variant.product', 'twig');
        $codeField->setLabel('sylius.ui.image');
        $codeField->setOptions(['template' => '@SyliusAdmin/Product/Grid/Field/image.html.twig']);
        $codeField->setPosition(1);
        $grid->addField($codeField);

        $codeField = Field::fromNameAndType('variant.code', 'string');
        $codeField->setLabel('sylius.ui.name');
        $codeField->setPosition(2);
        $grid->addField($codeField);
    }
}
