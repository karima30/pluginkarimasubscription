<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Ksante\SubscriptionPlugin\State\SubscriptionStates;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\OrderCheckoutStates;

class SubscriptionRepository extends EntityRepository
{

    //Getting the active subscriptions by customer
    public function findActiveSubscriptionsByCustomer($customerID, $orderClassName){
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join($orderClassName, 'o', 'WITH', 'o.subscriptionOrder = s.id')
            ->leftJoin('s.customer', 'customer')
            ->where('o.state != :cart')
            ->andWhere('s.state != :stopped'/*.' && s.state != :pauseAuto'*/)
            ->andWhere('customer.id = :customerID')
            ->setParameter('cart', OrderCheckoutStates::STATE_CART)
            ->setParameter('stopped', SubscriptionStates::STOPPED)
            //->setParameter('pauseAuto', SubscriptionStates::AUTO_PAUSED)
            ->setParameter('customerID', $customerID)
            ->getQuery()
            ->getResult()
            ;
    }

    //Getting the payed subscriptions
    public function getPayedSubscriptions($orderClassName) {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join($orderClassName, 'o', 'WITH', 'o.subscription = s.id')
            ->where('o.state != :cart')
            ->setParameter('cart', OrderCheckoutStates::STATE_CART)
            ;
    }

    //Getting the payed subscriptions by customer
    public function getPayedSubscriptionsByCustomer($customerID, $orderClassName) {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join($orderClassName, 'o', 'WITH', 'o.subscription = s.id')
            ->leftJoin('s.customer', 'customer')
            ->where('o.state != :cart')
            ->andWhere('customer.id = :customerID')
            ->setParameter('cart', OrderCheckoutStates::STATE_CART)
            ->setParameter('customerID', $customerID)
            ;
    }

    //Selecting if a customer has already an on going subscription
    public function ifCustomerHasSubscription($customerID){
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.customer', 'customer')
            ->andWhere('s.state != :onGoing')
            ->andWhere('customer.id = :customerID')
            ->setParameter('onGoing', SubscriptionStates::ON_GOING)
            ->setParameter('customerID', $customerID)
            ->getQuery()
            ->getResult()
            ;
    }

    //Get subscription by id
    public function getSubscriptionById($id) {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->andWhere('s.id = :id')
            ->setParameter('id', $id)
            ;
    }

    //Getting subscriptions by customer ID
    public function getSubscriptionsByCustomerID($customerID) {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->leftJoin('s.customer', 'customer')
            ->andWhere('customer.id = :customerID')
            ->setParameter('customerID', $customerID)
            ->getQuery()
            ->getResult()
            ;
    }
}
