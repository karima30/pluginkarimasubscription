<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ApiResource(
 *     normalizationContext={"groups"={"stabilization_number_of_days_per_week"}}
 * )
 * @ORM\Table(name="sylius_stabilization_number_of_days_per_week")
 */

class StabilizationNumberOfDaysPerWeek implements ResourceInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("stabilization_number_of_days_per_week")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true, name="number_of_days")
     * @Groups("stabilization_number_of_days_per_week")
     */
    private $numberOfDays = 1;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("stabilization_number_of_days_per_week")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationOptions", mappedBy="stabilizationNumberOfDaysPerWeek", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    private $stabilizationOptions;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getNumberOfDays(): int
    {
        return $this->numberOfDays;
    }

    /**
     * @param int $numberOfDays
     */
    public function setNumberOfDays(int $numberOfDays): void
    {
        $this->numberOfDays = $numberOfDays;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    public function countStabilizationOptions(): int
    {
        return $this->stabilizationOptions->count();
    }

    public function getStabilizationOptions()
    {
        return $this->stabilizationOptions;
    }

    public function setStabilizationOptions($stabilizationOptions)
    {
        $this->stabilizationOptions = $stabilizationOptions;
    }

    public function addStabilizationOption($stabilizationOption)
    {
        if (!$this->stabilizationOptions->contains($stabilizationOption)) {
            $this->stabilizationOptions[] = $stabilizationOption;
            $stabilizationOption->setStabilizationNumberOfDaysPerWeek($this);
        }

        return $this;
    }

    public function removeStabilizationOption($stabilizationOption)
    {
        if ($this->stabilizationOptions->contains($stabilizationOption)) {
            $this->stabilizationOptions->removeElement($stabilizationOption);
        }

        return $this;
    }
}
