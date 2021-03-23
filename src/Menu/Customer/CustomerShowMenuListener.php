<?php

namespace Ksante\SubscriptionPlugin\Menu\Customer;

use Knp\Menu\Util\MenuManipulator;
use Sylius\Bundle\AdminBundle\Event\CustomerShowMenuBuilderEvent;

final class CustomerShowMenuListener
{
    public function addShowSubscriptionsToAdminCustomer(CustomerShowMenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();
        $customer = $event->getCustomer();

        $menu
            ->addChild('subscription_index', [
                'route' => 'ksante_subscription_customer_show_subscriptions',
                'routeParameters' => ['customerID' => $customer->getId()],
            ])
            ->setAttribute('type', 'show')
            ->setAttribute('other', true)
            ->setLabel('ksante_subscription.ui.show_subscriptions')
        ;

        $manipulator = new MenuManipulator();
        $manipulator->moveToFirstPosition($menu['subscription_index']);
    }
}
