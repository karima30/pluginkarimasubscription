<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\CoreBundle\Doctrine\ORM\OrderRepository as BaseOrderRepository;
use Sylius\Component\Core\OrderCheckoutStates;

class OrderRepository extends BaseOrderRepository
{
    //Getting the subscription's orders
    public function findOrdersBySubscription($subscriptionID){
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->leftJoin('o.subscription', 'subscription')
            ->where('subscription.id = :subscriptionID')
            ->andWhere('o.state != :cart')
            ->setParameter('subscriptionID', $subscriptionID)
            ->setParameter('cart', OrderCheckoutStates::STATE_CART)
            ;
    }
}
