<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_product_selected_programs")
 */

class ProductSelectedPrograms implements ResourceInterface
{
    public function __construct()
    {
        $this->programs = new ArrayCollection();
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Sylius\Component\Product\Model\ProductInterface")
     * @ORM\JoinTable(name="product_selected_programs_programs",
     *      joinColumns={@ORM\JoinColumn(name="program_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="product_selected_program_id", referencedColumnName="id")}
     *      )
     */
    protected $programs;

    public function getId()
    {
        return $this->id;
    }

    public function countPrograms(): int
    {
        return $this->programs->count();
    }

    public function getPrograms()
    {
        return $this->programs;
    }

    public function setPrograms($programs): void
    {
        $this->programs = $programs;
    }

    public function addProgram(ProductInterface $program): self
    {
        if (!$this->programs->contains($program)) {
            $this->programs[] = $program;
        }

        return $this;
    }

    public function removeProgram(ProductInterface $program): self
    {
        if ($this->programs->contains($program)) {
            $this->programs->removeElement($program);
        }

        return $this;
    }
}
