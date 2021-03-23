<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Ksante\SubscriptionPlugin\State\OrderStates;
use Ksante\SubscriptionPlugin\State\SubscriptionStates;
use Sylius\Component\Core\Model\CustomerInterface;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\OrderShippingStates;
use Webmozart\Assert\Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_subscription")
 * @ApiResource(
 *     normalizationContext={"groups"={"subscription"}}
 * )
 */

class Subscription implements SubscriptionInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"subscription"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Product\Model\ProductVariantInterface")
     */
    private $program;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Core\Model\Customer")
     */
    private $customer;

    /**
     * @ORM\Column(type="string", nullable = FALSE)
     * @Groups({"subscription"})
     */
    private $state = SubscriptionStates::ON_GOING;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Core\Model\ChannelInterface")
     * @Groups({"subscription"})
     */
    protected $channel;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=TRUE, name="last_order_date")
     * @Groups({"subscription"})
     */
    protected $lastOrderDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=TRUE, name="first_order_date")
     * @Groups({"subscription"})
     */
    protected $firstOrderDate;

    /**
     * @ORM\Column(type="string", nullable = TRUE, name="token_value")
     */
    protected $tokenValue;

    /**
     * @ORM\OneToMany(targetEntity="Sylius\Component\Order\Model\OrderInterface", mappedBy="subscription", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    protected $orders;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionOrder", mappedBy="subscription", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    protected $subscriptionOrders;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionLog", mappedBy="subscription", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    protected $subscriptionLogs;

    /**
     * @ORM\ManyToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationOptions", inversedBy="subscriptions")
     * @ORM\JoinTable(name="subscription_stabilization_options")
     */
    protected $stabilizationOptions;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->orders = new ArrayCollection();
        $this->subscriptionOrders = new ArrayCollection();
        $this->subscriptionLogs = new ArrayCollection();
        $this->stabilizationOptions = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgram()
    {
        return $this->program;
    }

    public function setProgram($program): void
    {
        $this->program = $program;
    }

    public function getProduct()
    {
        return $this->program->getProduct();
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer): void
    {
        Assert::nullOrisInstanceOf($customer, CustomerInterface::class);

        $this->customer = $customer;
    }

    public function getUser()
    {
        if (null === $this->customer) {
            return null;
        }

        return $this->customer->getUser();
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        if(empty($this->state)) {
            return SubscriptionStates::ON_GOING;
        }
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getLastOrderDate()
    {
        return $this->lastOrderDate;
    }

    /**
     * @param \DateTime $lastOrderDate
     */
    public function setLastOrderDate(\DateTime $lastOrderDate): void
    {
        $this->lastOrderDate = $lastOrderDate;
    }

    public function getFirstOrderDate()
    {
        return $this->firstOrderDate;
    }

    /**
     * @param \DateTime $firstOrderDate
     */
    public function setFirstOrderDate(\DateTime $firstOrderDate): void
    {
        $this->firstOrderDate = $firstOrderDate;
    }

    public function getTokenValue(): ?string
    {
        return $this->tokenValue;
    }

    public function setTokenValue(?string $tokenValue): void
    {
        $this->tokenValue = $tokenValue;
    }

    public function countOrders(): int
    {
        return $this->getOrders()->count();
    }

    public function getOrders()
    {
        $orders = new ArrayCollection();
        foreach ($this->orders as $order) {
            if(!$order->isRegularizationOrder()) {
                $orders->add($order);
            }
        }
        return $orders;
    }

    public function setOrders($orders)
    {
        $this->orders = $orders;
    }

    public function addOrder($order)
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setSubscription($this);
        }

        return $this;
    }

    public function removeOrder($order)
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
        }

        return $this;
    }

    public function countSubscriptionOrders(): int
    {
        return $this->subscriptionOrders->count();
    }

    public function getSubscriptionOrders()
    {
        return $this->subscriptionOrders;
    }

    public function setSubscriptionOrders($subscriptionOrders)
    {
        $this->subscriptionOrders = $subscriptionOrders;
    }

    public function addSubscriptionOrder($subscriptionOrder)
    {
        if (!$this->subscriptionOrders->contains($subscriptionOrder)) {
            $this->subscriptionOrders[] = $subscriptionOrder;
            $subscriptionOrder->setSubscription($this);
        }

        return $this;
    }

    public function removeSubscriptionOrder($subscriptionOrder)
    {
        if ($this->subscriptionOrders->contains($subscriptionOrder)) {
            $this->subscriptionOrders->removeElement($subscriptionOrder);
        }

        return $this;
    }

    public function getCompletedOrdersCount(): int
    {
        $completedOrdersCount = 0;
        foreach ($this->getOrders() as $order) {
            if($order->getPaymentState() == OrderStates::STATE_PAID) {
                $completedOrdersCount++;
            }
        }
        return $completedOrdersCount;
    }

    public function getShippedOrdersCount(): int
    {
        $shippedOrdersCount = 0;
        foreach ($this->getOrders() as $order) {
            if($order->getShippingState() == OrderShippingStates::STATE_SHIPPED) {
                $shippedOrdersCount++;
            }
        }
        return $shippedOrdersCount;
    }

    public function getLatestOrder() {
        return $this->getOrders()->last();
    }

    public function getLatestSubscriptionOrder() {
        return $this->getSubscriptionOrders()->last();
    }

    /*public function getExpectedNumberOfOrders() {
        $subscriptionNumberOfOrdersBasedOnPeriodicity = 0;
        if(!empty($this->getOrders()[0])) {
            foreach ($this->getOrders()[0]->getItems() as $item) {
                if(!empty($item->getVariant()->getProduct()->getProgram())) {
                    if(empty($item->getVariant()->getProduct()->getProgram()->getMaximumSubscription())) {
                        $subscriptionNumberOfOrdersBasedOnPeriodicity = "_";
                        break;
                    } else {
                        if($item->getVariant()->getProduct()->getProgram()->getPeriodicity() == "monthly") {
                            $subscriptionNumberOfOrdersBasedOnPeriodicity = $item->getVariant()->getProduct()->getProgram()->getMaximumSubscription();
                        } else {
                            $subscriptionNumberOfOrdersBasedOnPeriodicity = ($item->getVariant()->getProduct()->getProgram()->getMaximumSubscription() / 4);
                        }
                    }
                }
            }
        }
        return round($subscriptionNumberOfOrdersBasedOnPeriodicity, 0);
    }*/

    public function countSubscriptionLogs(): int
    {
        return $this->subscriptionLogs->count();
    }

    public function getSubscriptionLogs()
    {
        return $this->subscriptionLogs;
    }

    public function setSubscriptionLogs($subscriptionLogs)
    {
        $this->subscriptionOrders = $subscriptionLogs;
    }

    public function addSubscriptionLog($subscriptionLog)
    {
        if (!$this->subscriptionLogs->contains($subscriptionLog)) {
            $this->subscriptionLogs[] = $subscriptionLog;
            $subscriptionLog->setSubscription($this);
        }

        return $this;
    }

    public function removeSubscriptionLogs($subscriptionLog)
    {
        if ($this->subscriptionLogs->contains($subscriptionLog)) {
            $this->subscriptionLogs->removeElement($subscriptionLog);
        }

        return $this;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    public function countStabilizationOptions(): int
    {
        return $this->stabilizationOptions->count();
    }

    public function getStabilizationOptions()
    {
        return $this->stabilizationOptions;
    }

    public function setStabilizationOptions($stabilizationOptions): void
    {
        $this->stabilizationOptions = $stabilizationOptions;
    }

    public function addStabilizationOption(StabilizationOptions $stabilizationOption): self
    {
        if (!$this->stabilizationOptions->contains($stabilizationOption)) {
            $this->stabilizationOptions[] = $stabilizationOption;
        }

        return $this;
    }

    public function removeStabilizationOption(StabilizationOptions $stabilizationOption): self
    {
        if ($this->stabilizationOptions->contains($stabilizationOption)) {
            $this->stabilizationOptions->removeElement($stabilizationOption);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isStabilizationProgram(): bool
    {
        return $this->program->getProduct()->getProgram()->isStabilizationProgram();
    }

    /**
     * @return bool
     */
    public function isSubscriptionOnPause(): bool
    {
        return $this->getState() == SubscriptionStates::PAUSED || $this->getState() == SubscriptionStates::AUTO_PAUSED;
    }

    /**
     * @return bool
     */
    public function isSubscriptionOnGoing(): bool
    {
        return $this->getState() == SubscriptionStates::ON_GOING;
    }

    /**
     * @return bool
     */
    public function showUnsubscribe(): bool
    {
        return !($this->getState() == SubscriptionStates::STOPPED || $this->getState() == SubscriptionStates::FINALIZED);
    }

    public function getNextOrderDate() {
        if(!empty($this->getLatestSubscriptionOrder()) && $this->getState() != SubscriptionStates::STOPPED && $this->getState() != SubscriptionStates::AUTO_PAUSED) {
            return $this->getLatestSubscriptionOrder()->getExpectedDeliveryDate();
        }
        return null;
    }

    /**
     * @Groups({"subscription"})
     */
    public function getProgramName() {
        return $this->getProgram()->getProduct()->getCode();
    }

    public function countRegularizationOrders(): int
    {
        return $this->getRegularizationOrders()->count();
    }

    public function getRegularizationOrders()
    {
        $regularizationOrders = new ArrayCollection();
        foreach ($this->orders as $order) {
            if($order->isRegularizationOrder()) {
                $regularizationOrders->add($order);
            }
        }
        return $regularizationOrders;
    }
}
