<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class SubscriptionLogRepository extends EntityRepository
{
    //Getting the subscription logs by subscription
    public function findLogsBySubscription($subscriptionID){
        return $this->createQueryBuilder('l')
            ->addSelect('l')
            ->leftJoin('l.subscription', 'subscription')
            ->where('subscription.id =:subscriptionID')
            ->setParameter('subscriptionID', $subscriptionID)
            ;
    }
}
