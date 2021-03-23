<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_program_categories_detail")
 * @ApiResource
 */
class ProgramCategoriesDetail implements ResourceInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"program"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
     * @Groups({"program"})
     */
    private $taxon;

    /**
     * @ORM\ManyToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\Program", inversedBy="programCategoriesDetails")
     */
    protected $program;

    /**
     * @ORM\Column(type="integer", nullable=false, name="minimum_number_of_products")
     * @Groups({"program"})
     */
    private $minimumNumberOfProducts;

    /**
     * @ORM\Column(type="integer", nullable=false, name="maximum_number_of_products")
     * @Groups({"program"})
     */
    private $maximumNumberOfProducts;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_obligatory")
     * @Groups({"program"})
     */
    private $isObligatory = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"program"})
     */
    private $priority = -1;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Program
     */
    public function getProgram() : Program
    {
        return $this->program;
    }

    /**
     * @param Program $program
     */
    public function setProgram($program): void
    {
        $this->program = $program;
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

    /**
     * @return bool
     */
    public function isObligatory(): bool
    {
        return $this->isObligatory;
    }

    /**
     * @param bool $isObligatory
     */
    public function setIsObligatory(bool $isObligatory): void
    {
        $this->isObligatory = $isObligatory;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getMinimumNumberOfProducts(): ?int
    {
        return $this->minimumNumberOfProducts;
    }

    public function setMinimumNumberOfProducts(?int $minimumNumberOfProducts): void
    {
        $this->minimumNumberOfProducts = $minimumNumberOfProducts;
    }

    public function getMaximumNumberOfProducts(): ?int
    {
        return $this->maximumNumberOfProducts;
    }

    public function setMaximumNumberOfProducts(?int $maximumNumberOfProducts): void
    {
        $this->maximumNumberOfProducts = $maximumNumberOfProducts;
    }

    public function getIsObligatory() {
        return $this->isObligatory;
    }

}
