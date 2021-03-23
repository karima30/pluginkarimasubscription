<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ksante\SubscriptionPlugin\State\OrderStates;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Order\Model\AdjustmentInterface;
use Webmozart\Assert\Assert;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_subscription_order")
 * @ApiResource(
 *     normalizationContext={"groups"={"subscription_order"}}
 * )
 */
class SubscriptionOrder implements OrderInterface
{
    use SubscriptionTrait;
    use TimestampableTrait;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"subscription_order"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Customer\Model\CustomerInterface")
     * @Groups({"subscription_order"})
     */
    private $customer;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Core\Model\ChannelInterface")
     * @Groups({"subscription_order"})
     */
    protected $channel;

    /**
     * @ORM\Column(type="string", nullable=false, name="currency_code")
     * @Groups({"subscription_order"})
     */
    protected $currencyCode;

    /**
     * @ORM\Column(type="string", nullable=false, name="locale_code")
     * @Groups({"subscription_order"})
     */
    protected $localeCode;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Promotion\Model\PromotionCouponInterface")
     * @ORM\JoinColumn(name="promotion_code_id", referencedColumnName="id")
     * @Groups({"subscription_order"})
     */
    protected $promotionCoupon;

    /**
     * @ORM\Column(type="string", nullable=false, name="checkout_state")
     * @Groups({"subscription_order"})
     */
    protected $checkoutState = OrderCheckoutStates::STATE_CART;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"subscription_order"})
     */
    protected $notes;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionOrderItem", mappedBy="order", cascade={"persist","remove"}, orphanRemoval=TRUE)
     * @Groups({"subscription_order"})
     */
    protected $items;

    /**
     * @ORM\Column(type="integer", nullable=true, name="items_total")
     */
    protected $itemsTotal = 0;

    /**
     * @ORM\Column(type="integer", nullable=true, name="adjustments_total")
     */
    protected $adjustmentsTotal = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $total = 0;

    /**
     * @ORM\ManyToMany(targetEntity="Sylius\Component\Promotion\Model\PromotionInterface")
     * @ORM\JoinTable(
     *     name="subscription_order_promotion",
     *     joinColumns={
     *          @ORM\JoinColumn(name="subscription_order_id", referencedColumnName="id")
     *     },
     *     inverseJoinColumns={
     *          @ORM\JoinColumn(name="promotion_id", referencedColumnName="id")
     *     }
     * )
     * @Groups({"subscription_order"})
     */
    protected $promotions;

    /**
     * @var AddressInterface|null
     * @ORM\OneToOne(targetEntity="Sylius\Component\Addressing\Model\AddressInterface" ,cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="shipping_address_id", referencedColumnName="id")
     * @Groups({"subscription_order"})
     */
    protected $shippingAddress;

    /**
     * @var AddressInterface|null
     * @ORM\OneToOne(targetEntity="Sylius\Component\Addressing\Model\AddressInterface" ,cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="billing_address_id", referencedColumnName="id")
     * @Groups({"subscription_order"})
     */
    protected $billingAddress;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=TRUE, name="expected_delivery_date")
     * @Groups({"subscription_order"})
     */
    protected $expectedDeliveryDate;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Order\Model\OrderInterface")
     */
    protected $order;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_validated")
     * @Groups({"subscription_order"})
     */
    private $isValidated = false;

    /**
     * @ORM\Column(type="string", nullable=true, name="coupon_code")
     * @Groups({"subscription_order"})
     */
    protected $couponCode;

    public function getId()
    {
        return $this->id;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer): void
    {
        $this->customer = $customer;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function setOrder($order): void
    {
        $this->order = $order;
    }

    public function getUser()
    {
        if (null === $this->customer) {
            return null;
        }

        return $this->customer->getUser();
    }

    public function getCheckoutState(): ?string
    {
        return $this->checkoutState;
    }

    public function setCheckoutState(?string $checkoutState): void
    {
        $this->checkoutState = $checkoutState;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function clearItems(): void
    {
        $this->items->clear();

        $this->recalculateItemsTotal();
    }

    public function countItems(): int
    {
        return $this->items->count();
    }

    public function addItem($item): void
    {
        if ($this->hasItem($item)) {
            return;
        }

        $this->itemsTotal += $item->getTotal();
        $this->items->add($item);
        $item->setOrder($this);

        $this->recalculateTotal();
    }

    /**
     * Items total + Adjustments total.
     */
    protected function recalculateTotal(): void
    {
        $this->total = $this->itemsTotal + $this->adjustmentsTotal;

        if ($this->total < 0) {
            $this->total = 0;
        }
    }

    protected function addToAdjustmentsTotal($adjustment): void
    {
        if (!$adjustment->isNeutral()) {
            $this->adjustmentsTotal += $adjustment->getAmount();
            $this->recalculateTotal();
        }
    }

    protected function subtractFromAdjustmentsTotal($adjustment): void
    {
        if (!$adjustment->isNeutral()) {
            $this->adjustmentsTotal -= $adjustment->getAmount();
            $this->recalculateTotal();
        }
    }

    public function getItemsTotal(): int
    {
        return $this->itemsTotal;
    }

    public function recalculateItemsTotal(): void
    {
        $this->itemsTotal = 0;
        foreach ($this->items as $item) {
            $this->itemsTotal += $item->getTotal();
        }

        $this->recalculateTotal();
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTotalQuantity(): int
    {
        $quantity = 0;

        foreach ($this->items as $item) {
            $quantity += $item->getQuantity();
        }

        return $quantity;
    }

    public function removeItem($item): void
    {
        if ($this->hasItem($item)) {
            $this->items->removeElement($item);
            $this->itemsTotal -= $item->getTotal();
            $this->recalculateTotal();
            $item->setOrder(null);
        }
    }

    public function hasItem($item): bool
    {
        return $this->items->contains($item);
    }

    public function getPromotionCoupon()
    {
        return $this->promotionCoupon;
    }

    public function setPromotionCoupon($coupon): void
    {
        $this->promotionCoupon = $coupon;
    }

    public function getPromotionSubjectTotal(): int
    {
        return $this->getItemsTotal();
    }

    public function getPromotionSubjectCount(): int
    {
        return $this->getTotalQuantity();
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): void
    {
        Assert::string($localeCode);

        $this->localeCode = $localeCode;
    }

    public function hasPromotion($promotion): bool
    {
        return $this->promotions->contains($promotion);
    }

    public function addPromotion($promotion)
    {
        if (!$this->hasPromotion($promotion)) {
            $this->promotions->add($promotion);
        }
    }

    public function removePromotion($promotion)
    {
        if ($this->hasPromotion($promotion)) {
            $this->promotions->removeElement($promotion);
        }
    }

    public function getPromotions()
    {
        return $this->promotions;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function getShippingAddress(): ?AddressInterface
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?AddressInterface $address): void
    {
        $this->shippingAddress = $address;
    }

    public function getBillingAddress(): ?AddressInterface
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?AddressInterface $address): void
    {
        $this->billingAddress = $address;
    }

    public function getExpectedDeliveryDate(): ?\DateTimeInterface
    {
        return $this->expectedDeliveryDate;
    }

    public function setExpectedDeliveryDate(?\DateTimeInterface $expectedDeliveryDate): void
    {
        $this->expectedDeliveryDate = $expectedDeliveryDate;
    }

    public function getAdjustments(?string $type = null): Collection
    {
        // TODO: Implement getAdjustments() method.
    }

    public function addAdjustment(AdjustmentInterface $adjustment): void
    {
        // TODO: Implement addAdjustment() method.
    }

    public function removeAdjustment(AdjustmentInterface $adjustment): void
    {
        // TODO: Implement removeAdjustment() method.
    }

    public function getAdjustmentsTotal(?string $type = null): int
    {
        // TODO: Implement getAdjustmentsTotal() method.
    }

    public function removeAdjustments(?string $type = null): void
    {
        // TODO: Implement removeAdjustments() method.
    }

    public function recalculateAdjustmentsTotal(): void
    {
        // TODO: Implement recalculateAdjustmentsTotal() method.
    }

    public function getCheckoutCompletedAt(): ?\DateTimeInterface
    {
        // TODO: Implement getCheckoutCompletedAt() method.
    }

    public function setCheckoutCompletedAt(?\DateTimeInterface $checkoutCompletedAt): void
    {
        // TODO: Implement setCheckoutCompletedAt() method.
    }

    public function isCheckoutCompleted(): bool
    {
        // TODO: Implement isCheckoutCompleted() method.
    }

    public function completeCheckout(): void
    {
        // TODO: Implement completeCheckout() method.
    }

    public function getNumber(): ?string
    {
        // TODO: Implement getNumber() method.
    }

    public function setNumber(?string $number): void
    {
        // TODO: Implement setNumber() method.
    }

    public function getState(): string
    {
        // TODO: Implement getState() method.
    }

    public function setState(string $state): void
    {
        // TODO: Implement setState() method.
    }

    public function isEmpty(): bool
    {
        // TODO: Implement isEmpty() method.
    }

    public function getAdjustmentsRecursively(?string $type = null): Collection
    {
        // TODO: Implement getAdjustmentsRecursively() method.
    }

    public function getAdjustmentsTotalRecursively(?string $type = null): int
    {
        // TODO: Implement getAdjustmentsTotalRecursively() method.
    }

    public function removeAdjustmentsRecursively(?string $type = null): void
    {
        // TODO: Implement removeAdjustmentsRecursively() method.
    }

    /**
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->isValidated;
    }

    /**
     * @param bool $isValidated
     */
    public function setIsValidated(bool $isValidated): void
    {
        $this->isValidated = $isValidated;
    }

    public function getProgramItemID() {
        foreach ($this->getItems() as $item) {
            if(!empty($item->getVariant()->getProduct()->getProgram())) {
                return $item->getId();
            }
        }
    }

    public function getOptionsItems() {
        $optionItems = new ArrayCollection();

        foreach ($this->items as $item) {
            if($item->isOption()) {
                $optionItems->add($item);
            }
        }
        return $optionItems;
    }

    public function getIsNotConvertedToOrder() {
        if($this->isValidated()) {
            return false;
        }
        return true;
    }

    /**
     * @Groups({"subscription_order"})
     */
    public function getProgramID() {
        return $this->getSubscription()->getProgram()->getProduct()->getProgram()->getId();
    }

    /**
     * @return string
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @param string $couponCode
     */
    public function setCouponCode($couponCode): void
    {
        $this->couponCode = $couponCode;
    }

}
