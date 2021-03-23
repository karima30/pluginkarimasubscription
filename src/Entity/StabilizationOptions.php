<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ApiResource(
 *     normalizationContext={"groups"={"stabilization_option"}}
 * )
 * @ORM\Table(name="sylius_stabilization_options")
 */

class StabilizationOptions implements ResourceInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("stabilization_option")
     */
    private $id;

    /**
     * @ORM\ManyToOne (targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationNumberOfDaysPerWeek", inversedBy="stabilizationOptions")
     * @ORM\JoinColumn(name="stabilization_number_of_days_per_week_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @Groups("stabilization_option")
     */
    protected $stabilizationNumberOfDaysPerWeek;

    /**
     * @ORM\ManyToOne (targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationCategory", inversedBy="stabilizationOptions")
     * @ORM\JoinColumn(name="stabilization_category_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @Groups("stabilization_option")
     */
    protected $stabilizationCategory;

    /**
     * @ORM\ManyToOne (targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationNumberOfWeeksInterval", inversedBy="stabilizationOptions")
     * @ORM\JoinColumn(name="stabilization_number_of_weeks_interval_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @Groups("stabilization_option")
     */
    protected $stabilizationNumberOfWeeksInterval;

    /**
     * @ORM\ManyToOne (targetEntity="Sylius\Component\Channel\Model\Channel")
     */
    protected $channel;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("stabilization_option")
     */
    protected $price = 0;

    /**
     * @ORM\Column(type="string", nullable=false, name="currency_code")
     * @Groups("stabilization_option")
     */
    protected $currencyCode;

    /**
     * @ORM\ManyToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\Subscription", mappedBy="stabilizationOptions")
     */
    private $subscriptions;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StabilizationNumberOfDaysPerWeek
     */
    public function getStabilizationNumberOfDaysPerWeek()
    {
        return $this->stabilizationNumberOfDaysPerWeek;
    }

    /**
     * @param StabilizationNumberOfDaysPerWeek $stabilizationNumberOfDaysPerWeek
     */
    public function setStabilizationNumberOfDaysPerWeek(StabilizationNumberOfDaysPerWeek $stabilizationNumberOfDaysPerWeek): void
    {
        $this->stabilizationNumberOfDaysPerWeek = $stabilizationNumberOfDaysPerWeek;
    }

    /**
     * @return StabilizationCategory
     */
    public function getStabilizationCategory()
    {
        return $this->stabilizationCategory;
    }

    /**
     * @param StabilizationCategory $stabilizationCategory
     */
    public function setStabilizationCategory(StabilizationCategory $stabilizationCategory): void
    {
        $this->stabilizationCategory = $stabilizationCategory;
    }

    /**
     * @return ChannelInterface
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param ChannelInterface $channel
     */
    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return intval($this->price);
    }

    /**
     * @param int $price
     */
    public function setPrice(int $price): void
    {
        $this->price = $price * 100;
    }

    /**
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode($currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function countSubscriptions(): int
    {
        return $this->subscriptions->count();
    }

    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    public function setSubscriptions($subscriptions): void
    {
        $this->subscriptions = $subscriptions;
    }

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->subscriptions->removeElement($subscription);
        }

        return $this;
    }

    public function getPriceOption () {
        return $this->price / 100;
    }

    /**
     * @return StabilizationNumberOfWeeksInterval
     */
    public function getStabilizationNumberOfWeeksInterval()
    {
        return $this->stabilizationNumberOfWeeksInterval;
    }

    /**
     * @param StabilizationNumberOfWeeksInterval $stabilizationNumberOfWeeksInterval
     */
    public function setStabilizationNumberOfWeeksInterval($stabilizationNumberOfWeeksInterval): void
    {
        $this->stabilizationNumberOfWeeksInterval = $stabilizationNumberOfWeeksInterval;
    }

}
