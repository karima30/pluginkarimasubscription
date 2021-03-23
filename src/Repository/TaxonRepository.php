<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Sylius\Bundle\TaxonomyBundle\Doctrine\ORM\TaxonRepository as BaseTaxonRepository;

class TaxonRepository extends BaseTaxonRepository
{
    //The search function to get taxons by their name in the auto complete components
    public function findTaxonByNamePart($phrase, $code, $locale = null, ?int $limit = null): array
    {
        if(empty($phrase)) {
            $phrase = $code;
        } elseif (is_array($phrase)) {   //Reported as a bug in sylius to change later on
            $phrase = $phrase[0];
        }
        return $this->createTranslationBasedQueryBuilder($locale)
            ->andWhere('o.code LIKE :name OR translation.name LIKE :name')
            ->setParameter('name', '%' . $phrase . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
            ;
    }
}
