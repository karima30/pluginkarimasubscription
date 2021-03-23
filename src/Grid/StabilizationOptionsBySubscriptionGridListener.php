<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class StabilizationOptionsBySubscriptionGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $numberOfDaysPerWeekField = Field::fromNameAndType('stabilizationNumberOfDaysPerWeek.numberOfDays', 'string');
        $numberOfDaysPerWeekField->setLabel('ksante_subscription.ui.stabilization_number_of_days_per_week');
        $numberOfDaysPerWeekField->setPosition(1);

        $grid->addField($numberOfDaysPerWeekField);

        $categoryField = Field::fromNameAndType('stabilizationCategory.name', 'string');
        $categoryField->setLabel('ksante_subscription.ui.stabilization_category');
        $categoryField->setPosition(2);

        $grid->addField($categoryField);

        $categoryTaxonField = Field::fromNameAndType('stabilizationCategory.categoryTaxon', 'twig');
        $categoryTaxonField->setLabel('sylius.ui.taxonomy');
        $categoryTaxonField->setOptions(['template' => '@SyliusAdmin/Product/Grid/Field/mainTaxon.html.twig']);
        $categoryTaxonField->setPosition(3);

        $grid->addField($categoryTaxonField);
    }
}
