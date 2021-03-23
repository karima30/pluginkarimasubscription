<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SubscriptionOrderRepository extends EntityRepository
{
    //Get subscription orders by subscription ID
    public function findSubscriptionOrdersBySubscriptionID($subscriptionID){
        return $this->createQueryBuilder('o')
            ->addSelect('o')
            ->leftJoin('o.subscription', 'subscription')
            ->where('subscription.id = :subscriptionID')
            //->andWhere('o.isValidated = 0')
            ->setParameter('subscriptionID', $subscriptionID)
            ;
    }
}
