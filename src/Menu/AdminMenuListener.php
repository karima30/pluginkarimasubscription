<?php
namespace Ksante\SubscriptionPlugin\Menu;

use Knp\Menu\Util\MenuManipulator;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
public function addItems(MenuBuilderEvent $event): void
{
$menu = $event->getMenu();

    $subscriptionSubMenu = $menu
        ->addChild('new')
        ->setLabel('ksante_subscription.ui.manage_subscriptions')
    ;

    $subscriptionSubMenu
        ->addChild('programs', array('route' => 'ksante_subscription_admin_product_index',
            'labelAttributes' => array('icon' => 'icon cube'),
        ))
        ->setLabel('ksante_subscription.ui.programs');
    ;

    $subscriptionSubMenu
        ->addChild('subscriptions', array('route' => 'ksante_subscription_admin_subscription_index',
            'labelAttributes' => array('icon' => 'linkify'),
        ))
        ->setLabel('ksante_subscription.ui.subscriptions');

    $subscriptionSubMenu
        ->addChild('subscriptions', array('route' => 'ksante_subscription_admin_subscription_index',
            'labelAttributes' => array('icon' => 'linkify'),
        ))
        ->setLabel('ksante_subscription.ui.subscriptions');


    $subscriptionSubMenu
        ->addChild('subscription_logs', array('route' => 'ksante_subscription_admin_subscription_logs_index',
            'labelAttributes' => array('icon' => 'clipboard outline'),
        ))
        ->setLabel('ksante_subscription.ui.subscription_logs');

    $subscriptionSubMenu
        ->addChild('stabilization_options', array('route' => 'ksante_subscription_admin_stabilization_options_index',
            'labelAttributes' => array('icon' => 'icon options'),
        ))
        ->setLabel('ksante_subscription.ui.stabilization_options');

    $manipulator = new MenuManipulator();
    $manipulator->moveToPosition($subscriptionSubMenu, 2);

}
}
