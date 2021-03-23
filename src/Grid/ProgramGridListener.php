<?php

namespace  Ksante\SubscriptionPlugin\Grid;

use Sylius\Component\Grid\Event\GridDefinitionConverterEvent;
use Sylius\Component\Grid\Definition\Field;

final class ProgramGridListener
{
    public function editFields(GridDefinitionConverterEvent $event): void
    {
        $grid = $event->getGrid();

        $codeField = Field::fromNameAndType('program.periodicity', 'twig');
        $codeField->setLabel('ksante_subscription.ui.periodicity');
        $codeField->setOptions(['template' => '@KsanteSubscriptionPlugin/grid/Field/state.html.twig', 'vars' => ['labels' => '@KsanteSubscriptionPlugin/Admin/Subscription/Subscription/Label/State']]);
        $grid->addField($codeField);

        $isStabilizationField = Field::fromNameAndType('program.isStabilizationProgram', 'twig');
        $isStabilizationField->setLabel('ksante_subscription.ui.is_stabilization_program');
        $isStabilizationField->setOptions(['template' => '@SyliusUi/Grid/Field/yesNo.html.twig']);

        $grid->addField($isStabilizationField);
    }
}
