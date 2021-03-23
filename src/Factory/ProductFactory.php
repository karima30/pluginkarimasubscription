<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Factory;

use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;

final class ProductFactory implements ProductFactoryInterface
{
    /** @var ProductFactoryInterface  */
    private $decoratedFactory;

    public function __construct(ProductFactoryInterface $factory)
    {
        $this->decoratedFactory = $factory;
    }

    public function createNew()
    {
        return $this->decoratedFactory->createNew();
    }

    public function createWithVariant(): ProductInterface
    {
        return $this->decoratedFactory->createWithVariant();
    }

}
