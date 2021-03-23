<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

interface SubscriptionInterface extends ResourceInterface, TimestampableInterface
{
    public function getCustomer();

    public function setCustomer($orders);

    public function getUser();

    public function getProgram();

    public function setProgram($variant);

    public function getState();

    public function setState(string $state);

    public function getProduct();
}
