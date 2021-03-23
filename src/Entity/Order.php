<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Order as BaseOrder;

/**
 * @ORM\Entity
 * @ApiResource
 * @ORM\Table(name="sylius_order")
 */
class Order extends BaseOrder
{
    use SubscriptionTrait;
}
