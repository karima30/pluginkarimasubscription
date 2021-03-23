<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class StabilizationOptionsGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $weeksIntervaleField = Field::fromNameAndType('stabilizationNumberOfWeeksInterval.weeksInterval', 'string');
        $weeksIntervaleField->setLabel('ksante_subscription.ui.stabilization_number_of_weeks_interval');
        $weeksIntervaleField->setPosition(1);

        $grid->addField($weeksIntervaleField);

        $numberOfDaysPerWeekField = Field::fromNameAndType('stabilizationNumberOfDaysPerWeek.numberOfDays', 'string');
        $numberOfDaysPerWeekField->setLabel('ksante_subscription.ui.stabilization_number_of_days_per_week');
        $numberOfDaysPerWeekField->setPosition(1);

        $grid->addField($numberOfDaysPerWeekField);

        $categoryField = Field::fromNameAndType('stabilizationCategory.name', 'string');
        $categoryField->setLabel('ksante_subscription.ui.stabilization_category');
        $categoryField->setPosition(2);

        $grid->addField($categoryField);
    }
}
