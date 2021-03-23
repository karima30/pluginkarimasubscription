<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ProgramDetailsRepository extends EntityRepository
{
    //Getting a program's products details (products along with their default quantity, priority, and step)
    public function getProgramDetailsByProduct($productID) {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.product', 'product')
            ->where('product.id = :productID')
            ->setParameter('productID', $productID)
            ->getQuery()
            ->getResult()
            ;
    }
}
