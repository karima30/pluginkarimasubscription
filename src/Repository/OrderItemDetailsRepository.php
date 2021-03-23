<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class OrderItemDetailsRepository extends EntityRepository
{
    //Getting the program's Selected products by order
    public function findOrderItemDetailsByOrder($orderID){
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->leftJoin('o.orderItem', 'orderItem')
            ->where('orderItem.order = :orderID')
            ->setParameter('orderID', $orderID)
            ;
    }
}
