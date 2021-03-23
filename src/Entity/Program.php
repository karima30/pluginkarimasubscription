<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Product\Model\ProductInterface;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_program")
 * @ApiResource(
 *     normalizationContext={"groups"={"program"}}
 * )
 * @ApiFilter(BooleanFilter::class, properties= {"isStabilizationProgram"})
 */
class Program implements ProgramInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"program", "order:read"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="Sylius\Component\Product\Model\ProductInterface", inversedBy="program", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="program_id", referencedColumnName="id", nullable=true)
     * @Groups({"program"})
     */
    private $product;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\ProgramDetails", mappedBy="program", cascade={"persist","remove"}, orphanRemoval=TRUE)
     * @Groups({"program"})
     */
    private $programDetails;

    /**
     * @ORM\OneToMany(targetEntity="Ksante\SubscriptionPlugin\Entity\ProgramCategoriesDetail", mappedBy="program", cascade={"persist","remove"}, orphanRemoval=TRUE)
     * @Groups({"program"})
     */
    private $programCategoriesDetails;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_stabilization_program")
     * @Groups({"program"})
     */
    private $isStabilizationProgram = false;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"program", "order:read"})
     */
    private $periodicity;

    /**
     * @ORM\Column(type="integer", nullable=true, name="minimum_subscription")
     * @Groups({"program", "order:read"})
     */
    private $minimumSubscription = 1;

    /**
     * @ORM\Column(type="integer", nullable=true, name="maximum_subscription")
     * @Groups({"program", "order:read"})
     */
    private $maximumSubscription;

    /**
     * @ORM\Column(name="program_price", type="integer", nullable=true)
     * @Groups({"program"})
     */
    private $programPrice;

    public function __construct()
    {
        $this->programDetails = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->programCategoriesDetails = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function countProgramDetails(): int
    {
        return $this->programDetails->count();
    }

    public function getProgramDetails()
    {
        return $this->programDetails;
    }

    public function setProgramDetails($programtDetails)
    {
        $this->productDetails = $programtDetails;
    }

    public function addProgramDetail($programtDetail)
    {
        if (!$this->programDetails->contains($programtDetail) && !empty($programtDetail->getProduct())) {
            $this->programDetails[] = $programtDetail;
            $programtDetail->setProgram($this);
        }

        return $this;
    }

    public function removeProgramDetail($programtDetail)
    {
        if ($this->programDetails->contains($programtDetail)) {
            $this->programDetails->removeElement($programtDetail);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPeriodicity()
    {
        return $this->periodicity;
    }

    /**
     * @param string $periodicity
     */
    public function setPeriodicity($periodicity): void
    {
        $this->periodicity = $periodicity;
    }

    /**
     * @return int
     */
    public function getMinimumSubscription()
    {
        return $this->minimumSubscription;
    }

    /**
     * @param int $minimumSubscription
     */
    public function setMinimumSubscription($minimumSubscription): void
    {
        $this->minimumSubscription = $minimumSubscription;
    }

    /**
     * @return int
     */
    public function getMaximumSubscription()
    {
        return $this->maximumSubscription;
    }

    /**
     * @param int $maximumSubscription
     */
    public function setMaximumSubscription(int $maximumSubscription): void
    {
        $this->maximumSubscription = $maximumSubscription;
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param ProductInterface $program
     */
    public function setProduct($program): void
    {
        $this->product = $program;
    }

    /**
     * @return bool
     */
    public function isStabilizationProgram(): bool
    {
        return $this->isStabilizationProgram;
    }

    /**
     * @param bool $isStabilizationProgram
     */
    public function setIsStabilizationProgram(bool $isStabilizationProgram): void
    {
        $this->isStabilizationProgram = $isStabilizationProgram;
    }

    /**
     * @return bool
     */
    public function getIsStabilizationProgram()
    {
        return $this->isStabilizationProgram;
    }

    public function countProgramCategoriesDetails(): int
    {
        return $this->programCategoriesDetails->count();
    }

    public function getProgramCategoriesDetails()
    {
        return $this->programCategoriesDetails;
    }

    public function setProgramCategoriesDetails($programCategoriesDetails)
    {
        $this->programCategoriesDetails = $programCategoriesDetails;
    }

    public function addProgramCategoriesDetail($programCategoriesDetail)
    {
        if (!$this->programCategoriesDetails->contains($programCategoriesDetail)) {
            $this->programCategoriesDetails[] = $programCategoriesDetail;
            $programCategoriesDetail->setProgram($this);
        }

        return $this;
    }

    public function removeProgramCategoriesDetail($programCategoriesDetail)
    {
        if ($this->programCategoriesDetails->contains($programCategoriesDetail)) {
            $this->programCategoriesDetails->removeElement($programCategoriesDetail);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getProgramPrice()
    {
        return $this->programPrice;
    }

    /**
     * @param int $programPrice
     */
    public function setProgramPrice($programPrice): void
    {
        $this->programPrice = $programPrice;
    }
}
