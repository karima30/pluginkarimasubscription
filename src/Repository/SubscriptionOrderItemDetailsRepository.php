<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SubscriptionOrderItemDetailsRepository extends EntityRepository
{
    //Getting the subscription order's selected products
    public function findItemDetailsByOrderId($orderID){
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->leftJoin('o.orderItem', 'orderItem')
            ->where('orderItem.order = :orderID')
            ->setParameter('orderID', $orderID)
            ;
    }
}
