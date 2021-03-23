<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SubscriptionOrderItemRepository extends EntityRepository
{
    //Find the program's subscription order item by subscription order item id which contains the detail of the selected products
    public function findProgramSubscriptionOrderItemBySubscriptionOrderId($subscriptionOrderItemID) {
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->leftJoin('o.variant', 'variant')
            ->innerJoin('variant.product', 'product')
            ->innerJoin('product.program', 'program')
            ->where('o.order = :subscriptionOrderID')
            ->andWhere('program is not null')
            ->setParameter('subscriptionOrderID', $subscriptionOrderItemID)
            ->getQuery()
            ->getSingleResult()
        ;
    }

    //Find the chosen subscription order item by subscription order id which contains the detail of the selected options
    public function findOptionsSubscriptionOrderItemBySubscriptionOrderId($subscriptionOrderID) {
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->where('o.order = :subscriptionOrderID')
            ->andWhere('o.isOption = 1')
            ->setParameter('subscriptionOrderID', $subscriptionOrderID)
            ;
    }
}
