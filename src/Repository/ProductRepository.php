<?php

namespace Ksante\SubscriptionPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Ksante\SubscriptionPlugin\Entity\ProductSelectedPrograms;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\ProductRepository as BaseProductRepository;

class ProductRepository extends BaseProductRepository
{
    //Get products depending on their type (simple product or program)
    public function getPrograms($isProgram): QueryBuilder{

        if($isProgram) $where = 'program is not null';
        else $where = 'program is null';

        $query = $this->createQueryBuilder('p')
            ->leftJoin('p.program', 'program')
            ->andWhere($where)
            ->andWhere('p.isRegularizationProduct is null OR p.isRegularizationProduct = 0')
            ;
        return $query;
    }

    //Finding the latest product in the product table
    public function findLatestProduct(){
        $query = $this->createQueryBuilder('p')->select('Max (p.id)');
        return $query->getQuery()->getSingleScalarResult();
    }

    //Getting products by if contains slug
    public function findProductCountSlug($slug){
        return $this->createQueryBuilder('p')
            ->addSelect('p')
            ->leftJoin('p.translations', 'translation')
            ->where('translation.slug LIKE :slug')
            ->setParameter('slug', '%'.$slug.'%')
            ->getQuery()
            ->getResult()
            ;
    }

//The search function to get products by their code in the auto complete components
    public function findProductsByPhrase($phrase, $code, /*string $locale, */?int $limit = null): array
    {
        if(empty($phrase)) {
            $phrase = $code;
        } elseif (is_array($phrase)) {   //Reported as a bug in sylius to change later on
            $phrase = $phrase[0];
        }
        $returnedData = [];
        $data = $this->createQueryBuilder('o')
            ->select('o.id, o.code')
            ->innerJoin('o.translations', 'translation'/*, 'WITH', 'translation.locale = :locale'*/)
            ->leftJoin('o.program', 'program')
            ->addSelect("CONCAT(translation.name, ' (', o.code, ')') AS productNameWithCode")
            ->andWhere('program is null')
            ->andWhere('o.isRegularizationProduct is null OR o.isRegularizationProduct = 0')
            ->andWhere('o.code LIKE :name OR translation.name LIKE :name')
            ->setParameter('name', '%' . $phrase . '%')
            //->setParameter('locale', $locale)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
        $data = array_unique($data, SORT_REGULAR);
        foreach ($data as $row) {
            $row['selected'] = true;
            $returnedData [] = $row;
        }
        return $returnedData;
    }

    //Getting a program's products
    public function getProductsBySelectedProgram($programID) {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->join(ProductSelectedPrograms::class, 's', 'WITH', 'p.productSelectedPrograms = s.id')
            ->leftJoin('s.programs', 'programs')
            ->where('programs.id = :programID')
            ->setParameter('programID', $programID)
            ->getQuery()
            ->getResult()
            ;
    }

    //Getting products by taxon ID
    public function getProductsByTaxonID($taxonId = null) {
        $queryBuilder = $this->createQueryBuilder('o');

        if (null !== $taxonId) {
            $queryBuilder
                ->innerJoin('o.productTaxons', 'productTaxon')
                ->andWhere('productTaxon.taxon = :taxonId')
                ->setParameter('taxonId', $taxonId)
            ;
        }

        return $queryBuilder;
    }
}
