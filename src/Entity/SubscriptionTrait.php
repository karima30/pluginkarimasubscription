<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

trait SubscriptionTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\Subscription")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $subscription;


    public function getSubscription()
    {
        return $this->subscription;
    }

    public function setSubscription($subscription): void
    {
        $this->subscription = $subscription;
    }

    /**
     * @return bool
     */
    public function isRegularizationOrder(): bool
    {
        foreach ($this->items as $item) {
            if($item->getVariant()->getProduct()->isRegularizationProduct() == true) {
                return true;
            }
        }
        return false;
    }

}
