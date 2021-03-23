<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_program_details")
 * @ApiResource
 */
class ProgramDetails implements ProgramDetailsInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"program"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Product\Model\ProductInterface")
     * @Groups({"program"})
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\Program", inversedBy="programDetails")
     */
    protected $program;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"program"})
     */
    private $quantity = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"program"})
     */
    private $step = 1;

    /** 
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"program"})
     */
    private $priority = -1;

    public function getId()
    {
        return $this->id;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product)
    {
        $this->product = $product;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity($quantity): void
    {
        if(empty($quantity)) {
            $quantity = 0;
        }
        $this->quantity = $quantity;
    }

    /**
     * @return int
     */
    public function getStep(): int
    {
        return $this->step;
    }

    public function setStep( $step): void
    {
        if(empty($step)) {
            $step = 1;
        }
        $this->step = $step;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority): void
    {
        if(empty($priority)) {
            $priority = -1;
        }
        $this->priority = $priority;
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

}
