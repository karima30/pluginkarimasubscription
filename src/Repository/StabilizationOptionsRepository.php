<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class StabilizationOptionsRepository extends EntityRepository
{
    //Getting a stabilization subscription's stabilization options
    public function findStabilizationOptionsBySubscription($subscriptionID){
        return $this->createQueryBuilder('s')
            ->addSelect('s')
            ->leftJoin('s.subscriptions', 'subscription')
            ->where('subscription.id = :subscriptionID')
            ->setParameter('subscriptionID', $subscriptionID)
            ;
    }

    //Getting the matching stabilization options to the user based on the number of weeks, channel Id, and the currency code
    public function getStabilizationOptionsByNumberOfWeeksChannelIdCurrencyCode($numberOfWeeks, $channelID, $currencyCode) {
        return $this->createQueryBuilder('s')
            ->addSelect('s')
            ->leftJoin('s.channel', 'channel')
            ->leftJoin('s.stabilizationNumberOfWeeksInterval', 'stabilizationNumberOfWeeksInterval')
            ->where('channel.id = :channelId')
            ->andWhere('stabilizationNumberOfWeeksInterval.fromWeek < :numberOfWeeks')
            ->andWhere('stabilizationNumberOfWeeksInterval.toWeek > :numberOfWeeks OR stabilizationNumberOfWeeksInterval.toWeek is null')
            ->andWhere('s.currencyCode = :currencyCode')
            ->setParameter('channelId', $channelID)
            ->setParameter('numberOfWeeks', $numberOfWeeks)
            ->setParameter('currencyCode', $currencyCode)
            ->getQuery()
            ->getResult()
            ;
    }
}
