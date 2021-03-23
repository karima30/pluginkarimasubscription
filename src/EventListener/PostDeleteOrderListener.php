<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class PostDeleteOrderListener
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function deleteSubscriptionOrder(GenericEvent $event): void
    {
        $order = $event->getSubject();
        $orderRepository = $this->container->get('sylius.repository.order');
        $subscriptionManager = $this->container->get('ksante_subscription.manager.subscription');

        if(!empty($order->getSubscription())) {
            $subscription = $order->getSubscription();
            $ordersBySubscriptionOrderCount = count($orderRepository->findOrdersBySubscription($subscription->getId())->getQuery()->getResult());
            if(!($ordersBySubscriptionOrderCount > 1)) {
                $subscriptionManager->remove($subscription);
            }
        }
        $subscriptionManager->flush();
    }
}
