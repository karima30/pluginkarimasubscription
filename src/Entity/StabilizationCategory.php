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
 *     normalizationContext={"groups"={"stabilization_category"}}
 * )
 * @ORM\Table(name="sylius_stabilization_category")
 */

    class StabilizationCategory implements ResourceInterface, TimestampableInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups("stabilization_category")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups("stabilization_category")
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups("stabilization_category")
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\StabilizationOptions", mappedBy="stabilizationCategory", cascade={"persist","remove"}, orphanRemoval=TRUE)
     */
    private $stabilizationOptions;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
     * @Groups("stabilization_category")
     */
    private $taxon;

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
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
            $stabilizationOption->setStabilizationCategory($this);
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
         * @return mixed
         * @Groups("stabilization_category")
         */
        public function getTaxon() //to update and refactor later
        {
            return ['id' => $this->taxon->getId()];
        }

        /**
         * @param mixed $taxon
         */
        public function setTaxon($taxon): void
        {
            $this->taxon = $taxon;
        }

        /**
         * @return mixed
         *
         */
        public function getTaxonID()
        {
            return $this->taxon;
        }

        public function getCategoryTaxon() {
            return $this->taxon;
        }

        public function setCategoryTaxon($taxon): void
        {
            $this->taxon = $taxon;
        }
}
