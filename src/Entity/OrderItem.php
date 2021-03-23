<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ksante\SubscriptionPlugin\Entity\OrderItemTrait;
use Sylius\Component\Core\Model\OrderItem as BaseOrderItem;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order_item")
 * @ApiResource
 */
class OrderItem extends BaseOrderItem
{
    use OrderItemTrait;
}
