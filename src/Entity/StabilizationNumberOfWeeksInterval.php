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
 *     normalizationContext={"groups"={"stabilization_number_of_days_per_week_interval"}}
 * )
 * @ORM\Table(name="sylius_stabilization_number_of_weeks_interval")
 */

class StabilizationNumberOfWeeksInterval implements ResourceInterface
{

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("stabilization_number_of_days_per_week_interval")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false, name="from_week")
     * @Groups("stabilization_number_of_days_per_week_interval")
     */
    private $fromWeek = 1;

    /**
     * @ORM\Column(type="integer", nullable=true, name="to_week")
     * @Groups("stabilization_number_of_days_per_week_interval")
     */
    private $toWeek;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("stabilization_number_of_days_per_week_interval")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationOptions", mappedBy="stabilizationNumberOfWeeksInterval", cascade={"persist","remove"}, orphanRemoval=TRUE)
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
            $stabilizationOption->setStabilizationNumberOfWeeksInterval($this);
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

    /**
     * @return int
     */
    public function getFromWeek(): int
    {
        return $this->fromWeek;
    }

    /**
     * @param int $fromWeek
     */
    public function setFromWeek(int $fromWeek): void
    {
        $this->fromWeek = $fromWeek;
    }

    /**
     * @return mixed
     */
    public function getToWeek()
    {
        return $this->toWeek;
    }

    /**
     * @param mixed $toWeek
     */
    public function setToWeek($toWeek): void
    {
        $this->toWeek = $toWeek;
    }


    public function getWeeksInterval() {
        return $this->getFromWeek().' - '.$this->getToWeek();
    }
}
