<?php

namespace Ksante\SubscriptionPlugin\Menu\Order;

use Knp\Menu\Util\MenuManipulator;
use Sylius\Bundle\AdminBundle\Event\OrderShowMenuBuilderEvent;

final class OrderMenuListener
{
    public function addShowSubscriptionToOrderMenu(OrderShowMenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $order = $event->getOrder();

        if($order->getSubscription()) {
            $menu
                ->addChild('subscription_index', [
                    'route' => 'ksante_subscription_show_subscription_by_id_from_order',
                    'routeParameters' => ['id' => $order->getSubscription()->getId()],
                ])
                ->setAttribute('type', 'show')
                ->setAttribute('other', true)
                ->setLabel('ksante_subscription.ui.show_subscription')
            ;

             $manipulator = new MenuManipulator();
             $manipulator->moveToFirstPosition($menu['subscription_index']);
        }

    }
}
