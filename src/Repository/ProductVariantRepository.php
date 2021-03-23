<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductVariantRepository as BaseProductVariantRepository;

class ProductVariantRepository extends BaseProductVariantRepository
{
    //The search function to get product variants by their code in the auto complete components
    public function findProductVariantByPhrase($phrase, $code, /*string $locale, */?int $limit = null): array
    {
        if(empty($phrase)) {
            $phrase = $code;
        } elseif (is_array($phrase)) {   //Reported as a bug in sylius to change later on
            $phrase = $phrase[0];
        }

        return $this->createQueryBuilder('p')
            ->select('p.id, p.code')
            ->innerJoin('p.product', 'product')
            ->innerJoin('product.translations', 'translation'/*, 'WITH', 'translation.locale = :locale'*/)
            ->leftJoin('product.program', 'program')
            ->addSelect("CONCAT(p.code, ' (', translation.name, ')') AS productNameWithCode")
            ->where('p.code LIKE :phrase OR translation.name LIKE :phrase')
            ->andWhere('product.isRegularizationProduct is null OR product.isRegularizationProduct = 0')
            ->andWhere('program is null')
            ->setParameter('phrase', '%' . $phrase . '%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    //Get product variants depending on their type (simple variant or program variant)
    public function getProgramVariants($isProgram): QueryBuilder{

        if($isProgram) $where = 'program is not null';
        else $where = 'program is null';

        $query = $this->createQueryBuilder('v')
            ->leftJoin('v.product', 'product')
            ->leftJoin('product.program', 'program')
            ->andWhere($where)
        ;
        return $query;
    }
}
