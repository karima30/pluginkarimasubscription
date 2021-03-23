<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use ApiPlatform\Core\Annotation\ApiResource;


/**
 * @ORM\Entity
 * @ApiResource
 * @ORM\Table(name="sylius_product")
 */
class Product extends BaseProduct
{
    use ProgramTrait;

    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
