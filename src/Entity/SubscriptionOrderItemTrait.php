<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

trait SubscriptionOrderItemTrait
{
    public function __construct()
    {
        parent::__construct();
        $this->orderItemDetails = new ArrayCollection();
    }

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionOrderItemDetails", mappedBy="orderItem", cascade={"persist","remove"}, orphanRemoval=TRUE)
     * @Groups({"subscription_order"})
     */
    private $orderItemDetails;

    public function countOrderItemDetails(): int
    {
        return $this->orderItemDetails->count();
    }

    public function getOrderItemDetails()
    {
        return $this->orderItemDetails;
    }

    public function setOrderItemDetails($orderItemDetails)
    {
        $this->productDetails = $orderItemDetails;
    }

    public function addOrderItemDetail($orderItemDetail)
    {
        if (!$this->orderItemDetails->contains($orderItemDetail)) {
            $this->orderItemDetails[] = $orderItemDetail;
            $orderItemDetail->setOrderItem($this);
        }

        return $this;
    }

    public function removeOrderItemDetail($orderItemDetail)
    {
        if ($this->orderItemDetails->contains($orderItemDetail)) {
            $this->orderItemDetails->removeElement($orderItemDetail);
        }

        return $this;
    }
}
