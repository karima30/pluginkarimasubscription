<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;
use Symfony\Component\Serializer\Annotation\Groups;

trait ProgramTrait
{
    /**
     * @var ProgramInterface|null
     * @ORM\OneToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\Program" , mappedBy="product", cascade={"persist", "remove"})
     */
    protected $program;

    protected $productNameWithCode;

    /**
     * @ORM\OneToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\ProductSelectedPrograms" ,cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="product_selected_programs_id", referencedColumnName="id")
     */
    protected $productSelectedPrograms;

    /**
     * @ORM\Column(type="string", nullable=true, name="nutritional_index")
     * @Groups("program")
     */
    private $nutritionIndex;

    /**
     * @ORM\Column(type="boolean", nullable=true, name="is_regularization_product")
     */
    private $isRegularizationProduct = false;

    /**
     * @return ProgramInterface|null
     */
    public function getProgram(): ?ProgramInterface
    {
        return $this->program;
    }

    /**
     * @param ProgramInterface|null $program
     * @required
     */
    public function setProgram(?ProgramInterface $program): void
    {
        if($program->getPeriodicity() != "") {
            $this->program = $program;
            $this->program->setProduct($this);
        }
    }

    /**
     * @return ProductSelectedPrograms
     */
    public function getProductSelectedPrograms()
    {
        return $this->productSelectedPrograms;
    }

    /**
     * @param ProductSelectedPrograms $productSelectedPrograms
     */
    public function setProductSelectedPrograms($productSelectedPrograms): void
    {
        $this->productSelectedPrograms = $productSelectedPrograms;
    }

    /**
     * @param string $productNameWithCode
     */
    public function setProductNameWithCode($productNameWithCode): void
    {
        $this->productNameWithCode = $productNameWithCode;
    }

    /**
     * @return string
     */
    public function getNutritionIndex()
    {
        return $this->nutritionIndex;
    }

    /**
     * @param string $nutritionIndex
     */
    public function setNutritionIndex($nutritionIndex): void
    {
        $this->nutritionIndex = $nutritionIndex;
    }

    public function isRegularizationProduct()
    {
        return $this->isRegularizationProduct;
    }

    /**
     * @param bool $isRegularizationProduct
     */
    public function setIsRegularizationProduct(bool $isRegularizationProduct): void
    {
        $this->isRegularizationProduct = $isRegularizationProduct;
    }

}
