<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\Entity;

trait ProductTranslationTrait
{
    /**
     * @var NutritionalInformationImageInterface|null
     * @ORM\OneToOne(targetEntity="Ksante\SubscriptionPlugin\Entity\NutritionalInformationImage" ,cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="nutritional_information_image_id", referencedColumnName="id")
     */
    protected $nutritionalInformationImage;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $ingredients;

    /**
     * @ORM\Column(type="text", nullable=true, name="preparation_tips")
     */
    private $preparationTips;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $recipe;

    /**
     * @ORM\Column(type="text", nullable=true, name="producer_info")
     */
    private $producerInfo;

    /**
     * @ORM\Column(type="text", nullable=true, name="conservation_tip")
     */
    private $conservationTip;

    /**
     * @ORM\Column(type="text", nullable=true, name="usage_tips")
     */
    private $usageTips;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $conditioning;

    /**
     * @return string
     */
    public function getIngredients()
    {
        return $this->ingredients;
    }

    /**
     * @param string $ingredients
     */
    public function setIngredients(string $ingredients = null): void
    {
        $this->ingredients = $ingredients;
    }

    /**
     * @return string
     */
    public function getPreparationTips()
    {
        return $this->preparationTips;
    }

    /**
     * @param string $preparationTips
     */
    public function setPreparationTips(string $preparationTips = null): void
    {
        $this->preparationTips = $preparationTips;
    }

    /**
     * @return string
     */
    public function getRecipe()
    {
        return $this->recipe;
    }

    /**
     * @param string $recipe
     */
    public function setRecipe(string $recipe = null): void
    {
        $this->recipe = $recipe;
    }

    /**
     * @return string
     */
    public function getProducerInfo()
    {
        return $this->producerInfo;
    }

    /**
     * @param string $producerInfo
     */
    public function setProducerInfo(string $producerInfo = null): void
    {
        $this->producerInfo = $producerInfo;
    }

    /**
     * @return string
     */
    public function getConservationTip()
    {
        return $this->conservationTip;
    }

    /**
     * @param string $conservationTip
     */
    public function setConservationTip(string $conservationTip = null): void
    {
        $this->conservationTip = $conservationTip;
    }

    /**
     * @return string
     */
    public function getUsageTips()
    {
        return $this->usageTips;
    }

    /**
     * @param string $usageTips
     */
    public function setUsageTips(string $usageTips = null): void
    {
        $this->usageTips = $usageTips;
    }

    /**
     * @return string
     */
    public function getConditioning()
    {
        return $this->conditioning;
    }

    /**
     * @param string $conditioning
     */
    public function setConditioning(string $conditioning = null): void
    {
        $this->conditioning = $conditioning;
    }

    /**
     * @return NutritionalInformationImageInterface|null
     */
    public function getNutritionalInformationImage(): ?NutritionalInformationImageInterface
    {
        return $this->nutritionalInformationImage;
    }

    /**
     * @param NutritionalInformationImageInterface|null $nutritionalInformationImage
     */
    public function setNutritionalInformationImage(?NutritionalInformationImageInterface $nutritionalInformationImage = null): void
    {
        $this->nutritionalInformationImage = $nutritionalInformationImage;
    }

}
