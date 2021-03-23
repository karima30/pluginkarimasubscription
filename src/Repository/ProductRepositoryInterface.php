<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Sylius\Component\Core\Repository\ProductRepositoryInterface as BaseProductRepositoryInterface;

interface ProductRepositoryInterface extends BaseProductRepositoryInterface
{
    //Get products depending on their type (simple product or program)
    public function getPrograms($isProgram): QueryBuilder;
}
