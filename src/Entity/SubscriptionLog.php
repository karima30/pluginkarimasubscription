<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;

/**
 * @ORM\Entity
 * @ApiResource
 * @ORM\Table(name="sylius_subscription_log")
 */

class SubscriptionLog implements ResourceInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\Subscription", inversedBy="subscriptionLogs")
     */
    protected $subscription;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Core\Model\AdminUserInterface")
     */
    private $agent;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Customer\Model\CustomerInterface")
     */
    private $customer;

    /**
     * @ORM\Column(type="string", nullable=TRUE, name="previous_status")
     */
    protected $previousStatus;

    /**
     * @ORM\Column(type="string", nullable=TRUE, name="new_status")
     */
    protected $newStatus;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }

    /**
     * @param Subscription $subscription
     */
    public function setSubscription($subscription): void
    {
        $this->subscription = $subscription;
    }

    /**
     * @return AdminUserInterface
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @param AdminUserInterface $agent
     */
    public function setAgent($agent): void
    {
        $this->agent = $agent;
    }

    public function getPreviousStatus()
    {
        return $this->previousStatus;
    }

    public function setPreviousStatus($previousStatus): void
    {
        $this->previousStatus = $previousStatus;
    }

    /**
     * @return string
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }

    /**
     * @param string $newStatus
     */
    public function setNewStatus($newStatus): void
    {
        $this->newStatus = $newStatus;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($customer): void
    {
        $this->customer = $customer;
    }

    public function isUpdatedByCustomer()
    {
        return !(empty($this->customer));
    }

    public function getUser()
    {
        if(!empty($this->agent)) {
            return $this->agent;
        }
        return $this->customer;
    }

}
