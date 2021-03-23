<?php

namespace Ksante\SubscriptionPlugin\Service;

use Ksante\SubscriptionPlugin\Entity\NutritionalInformationImage;
use Ksante\SubscriptionPlugin\Entity\Program;
use Ksante\SubscriptionPlugin\Entity\ProgramCategoriesDetail;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductImage;
use Sylius\Component\Core\Model\ProductTranslation;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProgramService
{
    /** @var ContainerInterface */
    protected $container;

    protected $productRepository;
    protected $productTranslationRepository;
    protected $productFactory;
    protected $programFactory;
    protected $programDetailsFactory;
    protected $productVariantFactory;
    protected $channelPricingFactory;
    protected $nutritionalInformationImageFactory;
    protected $productImageFactory;
    protected $productTranslationFactory;
    protected $programCategoriesDetailFactory;

    protected $productManager;
    protected $programManager;
    protected $programDetailsManager;
    protected $productTranslationManager;
    protected $productVariantManager;
    protected $channelPricingManager;
    protected $nutritionalInformationImageManager;
    protected $productImageManager;
    protected $programCategoriesDetailManager;


    public function __construct(ContainerInterface $container) {
        $this->container = $container;

        //Getting the required services including the Repositories, Managers, and Factories
        $this->productRepository = $this->container->get('sylius.repository.product');
        $this->productTranslationRepository = $this->container->get('sylius.repository.product_translation');

        $this->productFactory = $this->container->get('sylius.factory.product');
        $this->programFactory = $this->container->get('ksante_subscription_plugin.factory.program');
        $this->programDetailsFactory = $this->container->get('ksante_subscription.factory.program_details');
        $this->productTranslationFactory = $this->container->get('sylius.factory.product_translation');
        $this->productVariantFactory = $this->container->get('sylius.factory.product_variant');
        $this->channelPricingFactory = $this->container->get('sylius.factory.channel_pricing');
        $this->nutritionalInformationImageFactory = $this->container->get('ksante_subscription.factory.nutritional_information_image');
        $this->productImageFactory = $this->container->get('sylius.factory.product_image');
        $this->programCategoriesDetailFactory = $this->container->get('ksante_subscription.factory.program_categories_detail');

        $this->productManager = $this->container->get('sylius.manager.product');
        $this->programManager = $this->container->get('ksante_subscription_plugin.manager.program');
        $this->programDetailsManager = $this->container->get('ksante_subscription.manager.program_details');
        $this->productTranslationManager = $this->container->get('sylius.manager.product_translation');
        $this->productVariantManager = $this->container->get('sylius.manager.product_variant');
        $this->channelPricingManager = $this->container->get('sylius.manager.channel_pricing');
        $this->nutritionalInformationImageManager = $this->container->get('ksante_subscription.manager.nutritional_information_image');
        $this->productImageManager = $this->container->get('sylius.manager.product_image');
        $this->programCategoriesDetailManager = $this->container->get('ksante_subscription.manager.program_categories_detail');
    }

    //Duplicating a program with his data
    public function duplicateProgram(int $id)
    {
        //Getting the base program entity
        /** @var Product $baseProgram */
        $baseProgram = $this->productRepository->findOneBy(['id' => $id]);

        //Creating the new program entity
        /** @var Product $newProgram */
        $newProgram = $this->productFactory->createNew();

        $newProgramCode = $this->generateANewCodeForTheNewProgram();

        //Setting the new program basic data
        $this->settingProgramBasicData($baseProgram, $newProgram, $newProgramCode);

        //Setting the new program's channels
        $this->settingProgramChannels($baseProgram, $newProgram);

        //Setting the new program's variants
        $this->settingProgramVariant($baseProgram, $newProgram, $newProgramCode);

        //Setting the new program translations
        $this->settingProgramTranslations($baseProgram, $newProgram);

        //Setting data related to the program (duration, products with quantity and step, etc.)
        $this->settingProgramRelatedData($baseProgram, $newProgram);

        //Setting categories configuration
        $this->settingProgramCategories($baseProgram, $newProgram);

        $this->productManager->persist($newProgram);

        $this->productImageManager->flush();
        $this->nutritionalInformationImageManager->flush();
        $this->productTranslationManager->flush();
        $this->programDetailsManager->flush();
        $this->programManager->flush();
        $this->channelPricingManager->flush();
        $this->productVariantManager->flush();
        $this->programCategoriesDetailManager->flush();
        $this->productManager->flush();
    }

    //Setting the new program's variants
    public function settingProgramVariant(Product &$baseProgram, Product &$newProgram, $newProgramCode) {
        foreach ($baseProgram->getVariants() as $productVariant) {
            $newProductVariant = $this->productVariantFactory->createNew();
            $newProductVariant->setCode($newProgramCode);
            foreach ($productVariant->getChannelPricings() as $channelPricing) {
                $newChannelPricing = $this->channelPricingFactory->createNew();
                $newChannelPricing->setPrice($channelPricing->getPrice());
                $newChannelPricing->setOriginalPrice($channelPricing->getOriginalPrice());
                $newChannelPricing->setChannelCode($channelPricing->getChannelCode());
                $newProductVariant->addChannelPricing($newChannelPricing);
            }
            $newProgram->addVariant($newProductVariant);
        }
    }

    public function generateANewCodeForTheNewProgram() {
        //return hash('md5', $productRepository->findLatestProduct());
        return $this->productRepository->findLatestProduct();
    }

    //Setting the new program translations
    public function settingProgramTranslations(Product &$baseProgram, Product &$newProgram) {
        foreach ($baseProgram->getTranslations() as $productTranslation) {
            /** @var ProductTranslation $newProductTranslation */
            $newProductTranslation = $this->productTranslationFactory->createNew();

            $similarSlugCodeCount = count($this->productRepository->findProductCountSlug($productTranslation->getSlug()));
            $newProductTranslation->setSlug(($similarSlugCodeCount + 1).'_'.$productTranslation->getSlug());
            $newProductTranslation->setName($productTranslation->getName());
            $newProductTranslation->setDescription($productTranslation->getDescription());
            $newProductTranslation->setShortDescription($productTranslation->getShortDescription());
            $newProductTranslation->setMetaDescription($productTranslation->getMetaDescription());
            $newProductTranslation->setMetaKeywords($productTranslation->getMetaKeywords());
            $newProductTranslation->setLocale($productTranslation->getLocale());

            $newProductTranslation->setIngredients($productTranslation->getIngredients());
            $newProductTranslation->setPreparationTips($productTranslation->getPreparationTips());
            $newProductTranslation->setRecipe($productTranslation->getRecipe());
            $newProductTranslation->setProducerInfo($productTranslation->getProducerInfo());
            $newProductTranslation->setConservationTip($productTranslation->getConservationTip());
            $newProductTranslation->setUsageTips($productTranslation->getUsageTips());
            $newProductTranslation->setConditioning($productTranslation->getConditioning());

            if(!empty($productTranslation->getNutritionalInformationImage())) {
                /** @var NutritionalInformationImage $nutritionalInformationImage */
                $nutritionalInformationImage = $this->nutritionalInformationImageFactory->createNew();
                $nutritionalInformationImage->setPath($productTranslation->getNutritionalInformationImage()->getPath());
                $nutritionalInformationImage->setFile($productTranslation->getNutritionalInformationImage()->getFile());
                $nutritionalInformationImage->setType($productTranslation->getNutritionalInformationImage()->getType());

                $newProductTranslation->setNutritionalInformationImage($nutritionalInformationImage);
                $this->nutritionalInformationImageManager->persist($nutritionalInformationImage);
            }
            $newProgram->addTranslation($newProductTranslation);
            $this->productTranslationManager->persist($newProductTranslation);
        }
    }

    //Setting the new program's channels
    public function settingProgramChannels(Product &$baseProgram, Product &$newProgram) {
        foreach ($baseProgram->getChannels() as $channel) {
            $newProgram->addChannel($channel);
        }
    }

    public function settingProgramBasicData(Product &$baseProgram, Product &$newProgram, $newProgramCode) {
        //Setting the new program's basic data
        $newProgram->setCode($newProgramCode);
        $newProgram->setVariantSelectionMethod($baseProgram->getVariantSelectionMethod());
        $newProgram->setEnabled($baseProgram->isEnabled());
        $newProgram->setNutritionIndex($baseProgram->getNutritionIndex());
        foreach ($baseProgram->getImages() as $image) {
            /** @var ProductImage $newImage */
            $newImage = $this->productImageFactory->createNew();
            $newImage->setFile($image->getFile());
            $newImage->setType($image->getType());
            $newImage->setPath($image->getPath());
            $newImage->setOwner($image->getOwner());

            $newProgram->addImage($newImage);
            $this->productImageManager->persist($newImage);
        }
    }

    //Setting data related to the program (duration, products with quantity and step, etc.)
    public function settingProgramRelatedData(Product &$baseProgram, Product &$newProgram) {
        /** @var Program $programDetail */
        $programDetail = $this->programFactory->createNew();
        $programDetail->setPeriodicity($baseProgram->getProgram()->getPeriodicity());
        $programDetail->setIsStabilizationProgram($baseProgram->getProgram()->isStabilizationProgram());

        foreach ($baseProgram->getProgram()->getProgramDetails() as $programDetailInfo) {
            $newProgramDetails = $this->programDetailsFactory->createNew();
            $newProgramDetails->setStep($programDetailInfo->getStep());
            $newProgramDetails->setQuantity($programDetailInfo->getQuantity());
            $newProgramDetails->setPriority($programDetailInfo->getPriority());
            $newProgramDetails->setProduct($programDetailInfo->getProduct());
            $newProgramDetails->setProgram($programDetailInfo);

            $programDetail->addProgramDetail($newProgramDetails);

            $this->programDetailsManager->persist($newProgramDetails);
        }
        $newProgram->setProgram($programDetail);

        $this->programManager->persist($programDetail);
    }

    //Setting categories configuration
    public function settingProgramCategories(Product &$baseProgram, Product &$newProgram) {
        foreach ($baseProgram->getProgram()->getProgramCategoriesDetails() as $baseProgramCategoriesDetail) {
            /** @var ProgramCategoriesDetail $newProgramCategoryDetail */
            $newProgramCategoryDetail = $this->programCategoriesDetailFactory->createNew();
            $newProgramCategoryDetail->setTaxon($baseProgramCategoriesDetail->getTaxon());
            $newProgramCategoryDetail->setIsObligatory($baseProgramCategoriesDetail->isObligatory());
            $newProgramCategoryDetail->setMaximumNumberOfProducts($baseProgramCategoriesDetail->gnetMaximumNumberOfProducts());
            $newProgramCategoryDetail->setMinimumNumberOfProducts($baseProgramCategoriesDetail->getMinimumNumberOfProducts());
            $newProgramCategoryDetail->setPriority($baseProgramCategoriesDetail->getPriority());
            $newProgramCategoryDetail->setProgram($newProgram->getProgram());

            $this->programCategoriesDetailManager->persist($newProgramCategoryDetail);

            $newProgram->getProgram()->addProgramCategoriesDetail($newProgramCategoryDetail);
        }

        $this->programManager->persist($newProgram->getProgram());
    }
}
