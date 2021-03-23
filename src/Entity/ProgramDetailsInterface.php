<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Sylius\Component\Resource\Model\ResourceInterface;

interface ProgramDetailsInterface extends ResourceInterface
{
    public function getProduct();

    public function setProduct($product);

    public function getProgram() : Program;

    public function setProgram(Program $subscription);
}
