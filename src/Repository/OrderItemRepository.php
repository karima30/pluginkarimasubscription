<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\OrderBundle\Doctrine\ORM\OrderItemRepository as BaseOrderItemRepository;

class OrderItemRepository extends BaseOrderItemRepository
{
    //Find the chosen order item by order id which contains the detail of the selected options
    public function findOptionsOrderItemByOrderId($orderID) {
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->where('o.order = :orderId')
            ->andWhere('o.isOption = 1')
            ->setParameter('orderId', $orderID)
            ;
    }
}
