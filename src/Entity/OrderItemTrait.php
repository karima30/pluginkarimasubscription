<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Taxonomy\Model\TaxonInterface;

trait OrderItemTrait
{
    public function __construct()
    {
        parent::__construct();
        $this->orderItemDetails = new ArrayCollection();
    }

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\OrderItemDetails", mappedBy="orderItem", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    private $orderItemDetails;


    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
     */
    private $taxon;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_option")
     */
    private $isOption = false;

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

    public function setUnitPrice(int $unitPrice): void
    {
        /*if(!empty($this->getOrder()) && !empty($this->getVariant()->getProduct()->getProgram()) && $this->getVariant()->getProduct()->getProgram()->isStabilizationProgram() == true && $this->getOrder()->getSubscription()) {
            $realPrice = 0;
            foreach ($this->getOrder()->getSubscription()->getStabilizationOptions() as $stabilizationOption) {
                $realPrice += $stabilizationOption->getPrice();
            }
            $unitPrice = $realPrice;;
        }*/

        if(!empty($this->getVariant()->getProduct()->getProgram()) && !empty($this->getVariant()->getProduct()->getProgram()->getProgramPrice())) {
            $unitPrice = $this->getVariant()->getProduct()->getProgram()->getProgramPrice();
        }
        $this->unitPrice = $unitPrice;
        $this->recalculateUnitsTotal();
    }

    /**
     * @return TaxonInterface
     */
    public function getTaxon()
    {
        return $this->taxon;
    }

    /**
     * @param TaxonInterface $taxon
     */
    public function setTaxon($taxon): void
    {
        $this->taxon = $taxon;
    }

    public function isOption()
    {
        return $this->isOption;
    }

    /**
     * @param bool $isOption
     */
    public function setIsOption(bool $isOption): void
    {
        $this->isOption = $isOption;
    }
}
