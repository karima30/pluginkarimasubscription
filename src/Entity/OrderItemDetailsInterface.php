<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface OrderItemDetailsInterface extends ResourceInterface, TimestampableInterface
{

    public function getVariant();

    public function setVariant($variant);

    public function getProduct();

    public function getQuantity();

    public function setQuantity(int $quantity);

    public function getOrderItem();

    public function setOrderItem($orderItem);
}
