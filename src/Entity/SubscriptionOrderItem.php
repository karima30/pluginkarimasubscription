<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Order\Model\OrderItemUnitInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Webmozart\Assert\Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_subscription_order_item")
 * @ApiResource
 */
class SubscriptionOrderItem implements OrderItemInterface
{
    use SubscriptionOrderItemTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"subscription_order"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Product\Model\ProductVariantInterface")
     * @Groups({"subscription_order"})
     */
    protected $variant;

    /**
     * @ORM\Column(type="string", nullable=true, name="product_name")
     * @Groups({"subscription_order"})
     */
    protected $productName;

    /**
     * @ORM\Column(type="string", nullable=true, name="variant_name")
     * @Groups({"subscription_order"})
     */
    protected $variantName;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"subscription_order"})
     */
    protected $quantity = 0;

    /**
     * @ORM\Column(type="integer", nullable=true, name="unit_price")
     * @Groups({"subscription_order"})
     */
    protected $unitPrice = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"subscription_order"})
     */
    protected $total = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $immutable = false;

    /**
     * @ORM\ManyToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionOrder", inversedBy="items")
     */
    protected $order;

    /**
     * @var Collection|OrderItemUnitInterface[]
     *
     * @psalm-var Collection<array-key, OrderItemUnitInterface>
     */
    protected $units;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
     * @Groups({"subscription_order"})
     */
    private $taxon;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_option")
     */
    private $isOption = false;

    /** @var int */
    protected $unitsTotal = 0;

    /** @var int */
    protected $adjustmentsTotal = 0;


    public function __construct()
    {
        /** @var ArrayCollection<array-key, AdjustmentInterface> $this->adjustments */
        $this->adjustments = new ArrayCollection();

        /** @var ArrayCollection<array-key, OrderItemUnitInterface> $this->units */
        $this->units = new ArrayCollection();

        $this->orderItemDetails = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->order;
    }

    public function setOrder(?OrderInterface $order): void
    {
        $currentOrder = $this->getOrder();
        if ($currentOrder === $order) {
            return;
        }

        $this->order = null;

        if (null !== $currentOrder) {
            $currentOrder->removeItem($this);
        }

        if (null === $order) {
            return;
        }

        $this->order = $order;

        if (!$order->hasItem($this)) {
            $order->addItem($this);
        }
    }


    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
        $this->recalculateUnitsTotal();
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function recalculateAdjustmentsTotal(): void
    {
        $this->adjustmentsTotal = 0;

        foreach ($this->adjustments as $adjustment) {
            if (!$adjustment->isNeutral()) {
                $this->adjustmentsTotal += $adjustment->getAmount();
            }
        }

        $this->recalculateTotal();
    }

    public function recalculateUnitsTotal(): void
    {
        $this->unitsTotal = 0;

        foreach ($this->units as $unit) {
            $this->unitsTotal += $unit->getTotal();
        }

        $this->recalculateTotal();
    }

    public function equals(OrderItemInterface $orderItem): bool
    {
        return $this === $orderItem;
    }

    public function isImmutable(): bool
    {
        return $this->immutable;
    }

    public function setImmutable(bool $immutable): void
    {
        $this->immutable = $immutable;
    }

    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(OrderItemUnitInterface $unit): void
    {
        if ($this !== $unit->getOrderItem()) {
            throw new \LogicException('This order item unit is assigned to a different order item.');
        }

        if (!$this->hasUnit($unit)) {
            $this->units->add($unit);

            ++$this->quantity;
            $this->unitsTotal += $unit->getTotal();
            $this->recalculateTotal();
        }
    }

    public function removeUnit(OrderItemUnitInterface $unit): void
    {
        if ($this->hasUnit($unit)) {
            $this->units->removeElement($unit);

            --$this->quantity;
            $this->unitsTotal -= $unit->getTotal();
            $this->recalculateTotal();
        }
    }

    public function hasUnit(OrderItemUnitInterface $unit): bool
    {
        return $this->units->contains($unit);
    }

    public function getAdjustments(?string $type = null): Collection
    {
        if (null === $type) {
            return $this->adjustments;
        }

        return $this->adjustments->filter(static function (AdjustmentInterface $adjustment) use ($type) {
            return $type === $adjustment->getType();
        });
    }

    public function getAdjustmentsRecursively(?string $type = null): Collection
    {
        $adjustments = clone $this->getAdjustments($type);

        foreach ($this->units as $unit) {
            foreach ($unit->getAdjustments($type) as $adjustment) {
                $adjustments->add($adjustment);
            }
        }

        return $adjustments;
    }

    public function addAdjustment($adjustment): void
    {
        if (!$this->hasAdjustment($adjustment)) {
            $this->adjustments->add($adjustment);
            $this->addToAdjustmentsTotal($adjustment);
            $adjustment->setAdjustable($this);
        }
    }

    public function removeAdjustment($adjustment): void
    {
        if (!$adjustment->isLocked() && $this->hasAdjustment($adjustment)) {
            $this->adjustments->removeElement($adjustment);
            $this->subtractFromAdjustmentsTotal($adjustment);
            $adjustment->setAdjustable(null);
        }
    }

    public function hasAdjustment(AdjustmentInterface $adjustment): bool
    {
        return $this->adjustments->contains($adjustment);
    }

    public function getAdjustmentsTotal(?string $type = null): int
    {
        if (null === $type) {
            return $this->adjustmentsTotal;
        }

        $total = 0;
        foreach ($this->getAdjustments($type) as $adjustment) {
            if (!$adjustment->isNeutral()) {
                $total += $adjustment->getAmount();
            }
        }

        return $total;
    }

    public function getAdjustmentsTotalRecursively(?string $type = null): int
    {
        $total = 0;

        foreach ($this->getAdjustmentsRecursively($type) as $adjustment) {
            if (!$adjustment->isNeutral()) {
                $total += $adjustment->getAmount();
            }
        }

        return $total;
    }

    public function removeAdjustments(?string $type = null): void
    {
        foreach ($this->getAdjustments($type) as $adjustment) {
            $this->removeAdjustment($adjustment);
        }
    }

    public function removeAdjustmentsRecursively(?string $type = null): void
    {
        $this->removeAdjustments($type);
        foreach ($this->units as $unit) {
            $unit->removeAdjustments($type);
        }
    }

    /**
     * Recalculates total after units total or adjustments total change.
     */
    protected function recalculateTotal(): void
    {
        $this->total = $this->unitsTotal + $this->adjustmentsTotal;

        if ($this->total < 0) {
            $this->total = 0;
        }

        if (null !== $this->order) {
            $this->order->recalculateItemsTotal();
        }
    }

    protected function addToAdjustmentsTotal(AdjustmentInterface $adjustment): void
    {
        if (!$adjustment->isNeutral()) {
            $this->adjustmentsTotal += $adjustment->getAmount();
            $this->recalculateTotal();
        }
    }

    protected function subtractFromAdjustmentsTotal(AdjustmentInterface $adjustment): void
    {
        if (!$adjustment->isNeutral()) {
            $this->adjustmentsTotal -= $adjustment->getAmount();
            $this->recalculateTotal();
        }
    }

    public function getVariant(): ?ProductVariantInterface
    {
        return $this->variant;
    }

    public function setVariant(?ProductVariantInterface $variant): void
    {
        $this->variant = $variant;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->variant->getProduct();
    }

    public function getProductName(): ?string
    {
        return $this->productName ?: $this->variant->getProduct()->getName();
    }

    public function setProductName(?string $productName): void
    {
        $this->productName = $productName;
    }

    public function getVariantName(): ?string
    {
        return $this->variantName ?: $this->variant->getName();
    }

    public function setVariantName(?string $variantName): void
    {
        $this->variantName = $variantName;
    }

    /**
     * Returns sum of neutral and non neutral tax adjustments on order item and total tax of units.
     */
    public function getTaxTotal(): int
    {
        $taxTotal = 0;

        foreach ($this->getAdjustments(\Sylius\Component\Core\Model\AdjustmentInterface::TAX_ADJUSTMENT) as $taxAdjustment) {
            $taxTotal += $taxAdjustment->getAmount();
        }

        foreach ($this->units as $unit) {
            /** @var \Sylius\Component\Core\Model\OrderItemUnitInterface $unit */
            Assert::isInstanceOf($unit, OrderItemUnitInterface::class);

            $taxTotal += $unit->getTaxTotal();
        }

        return $taxTotal;
    }

    /**
     * Returns single unit price lowered by order unit promotions (each unit must have the same unit promotion discount)
     */
    public function getDiscountedUnitPrice(): int
    {
        if ($this->units->isEmpty()) {
            return $this->unitPrice;
        }

        $firstUnit = $this->units->first();

        /** @var OrderItemUnitInterface $firstUnit */
        Assert::isInstanceOf($firstUnit, OrderItemUnitInterface::class);

        return
            $this->unitPrice +
            $firstUnit->getAdjustmentsTotal(AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT)
            ;
    }

    public function getFullDiscountedUnitPrice(): int
    {
        if ($this->units->isEmpty()) {
            return $this->unitPrice;
        }

        $firstUnit = $this->units->first();

        /** @var OrderItemUnitInterface $firstUnit */
        Assert::isInstanceOf($firstUnit, OrderItemUnitInterface::class);

        return
            $this->unitPrice +
            $firstUnit->getAdjustmentsTotal(AdjustmentInterface::ORDER_UNIT_PROMOTION_ADJUSTMENT) +
            $firstUnit->getAdjustmentsTotal(AdjustmentInterface::ORDER_PROMOTION_ADJUSTMENT)
            ;
    }

    public function getSubtotal(): int
    {
        return $this->getDiscountedUnitPrice() * $this->quantity;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
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
