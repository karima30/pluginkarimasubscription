<?php

namespace Ksante\SubscriptionPlugin\Service;

use Ksante\SubscriptionPlugin\Controller\ApiResponses;
use Ksante\SubscriptionPlugin\Entity\Program;
use Ksante\SubscriptionPlugin\Entity\ProgramCategoriesDetail;
use Ksante\SubscriptionPlugin\Entity\Subscription;
use Ksante\SubscriptionPlugin\Entity\SubscriptionOrder;
use Ksante\SubscriptionPlugin\Entity\SubscriptionOrderItem;
use Ksante\SubscriptionPlugin\Entity\SubscriptionOrderItemDetails;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SubscriptionOrderService extends ResourceController
{
    /** @var ContainerInterface */
    protected $container;

    /** @var SessionInterface */
    private $session;

    protected $taxonRepository;
    protected $productVariantRepository;
    protected $subscriptionOrderRepository;
    protected $subcriptionOrderItemRepository;
    protected $subcriptionOrderItemDetailsRepository;
    protected $programCategoryDetailRepository;
    protected $promotionCouponRepository;

    protected $subcriptionOrderItemFactory;
    protected $subcriptionOrderItemDetailsFactory;

    protected $subcriptionOrderManager;
    protected $subcriptionOrderItemManager;
    protected $subcriptionOrderItemDetailsManager;

    public function __construct(ContainerInterface $container, SessionInterface $session) {
        $this->container = $container;

        $this->session = $session;

        $this->productVariantRepository = $this->container->get('sylius.repository.product_variant');
        $this->taxonRepository = $this->container->get('sylius.repository.taxon');
        $this->subscriptionOrderRepository = $this->container->get('ksante_subscription.repository.subscription_order');
        $this->subcriptionOrderItemRepository = $this->container->get('ksante_subscription.repository.subscription_order_item');
        $this->subcriptionOrderItemDetailsRepository = $this->container->get('ksante_subscription.repository.subscription_order_item_details');
        $this->programCategoryDetailRepository = $this->container->get('ksante_subscription.repository.program_categories_detail');
        $this->promotionCouponRepository = $this->container->get('sylius.repository.promotion_coupon');

        $this->subcriptionOrderItemFactory = $this->container->get('ksante_subscription.factory.subscription_order_item');
        $this->subcriptionOrderItemDetailsFactory = $this->container->get('ksante_subscription.factory.subscription_order_item_details');

        $this->subcriptionOrderManager = $this->container->get('ksante_subscription.manager.subscription_order');
        $this->subcriptionOrderItemManager = $this->container->get('ksante_subscription.manager.subscription_order_item');
        $this->subcriptionOrderItemDetailsManager = $this->container->get('ksante_subscription.manager.subscription_order_item_details');
    }

    //Getting the program's subscription order item by subscription order id which contains the detail of the selected products
    public function findProgramSubscriptionOrderItemBySubscriptionOrderId($subscriptionOrderId) {
        return $this->subcriptionOrderItemRepository->findProgramSubscriptionOrderItemBySubscriptionOrderId($subscriptionOrderId);
    }

    //Updating the selected products by subscription order id and by giving the list of the new selected products
    public function updateSubscriptionOrderSelectedProductsFromFront($parameters) {
        if (!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new JsonResponse(APIResponses::AUTHENTICATION_REQUIRED, Response::HTTP_BAD_REQUEST);
        }
        if(!array_key_exists('id', $parameters)) {
            return new JsonResponse(APIResponses::UNNPROVIDED_SUBSCRIPTION_ORDER_ID, Response::HTTP_BAD_REQUEST);
        }
        if(!array_key_exists('newSelectedProducts', $parameters)) {
            return new JsonResponse(APIResponses::UNNPROVIDED_NEW_SELECTED_PRODUCTS_LIST, Response::HTTP_BAD_REQUEST);
        }
        return $this->updateSubscriptionOrderSelectedProducts($parameters['id'], $parameters['newSelectedProducts']);
    }

    //Updating the selected products by subscription order id and by giving the list of the new selected products
    public function updateSubscriptionOrderSelectedProducts($subscriptionOrderId, $newSelectedProductsList) {
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->subscriptionOrderRepository->findOneBy(['id' => $subscriptionOrderId]);
        if(empty($subscriptionOrder)) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        if($subscriptionOrder->isValidated()) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_IS_ALREADY_VALIDATED, Response::HTTP_BAD_REQUEST);
        }

        $checkingObligatoryCategories = $this->checkExistanceOfAllObligatoryCategories($subscriptionOrder, $newSelectedProductsList);
        if($checkingObligatoryCategories instanceof JsonResponse) {
            return $checkingObligatoryCategories;
        }

        $checkingProductsCount = $this->checkingTheSelectedProductsCountBasedOnTheProgramAndCategory($subscriptionOrder, $newSelectedProductsList, 1);
        if($checkingProductsCount instanceof JsonResponse) {
            return $checkingProductsCount;
        }

        /** @var SubscriptionOrderItem $subscriptionOrderItem */
        $subscriptionOrderItem = $this->subcriptionOrderItemRepository->findOneBy(['id' => $subscriptionOrder->getProgramItemID()]);
        if(empty($subscriptionOrderItem)) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_DOES_NOT_EXIST_ITEM, Response::HTTP_BAD_REQUEST);
        }

        $this->removeDeletedSelectedProducts($subscriptionOrderItem, $newSelectedProductsList);

        $settingNewSelectedProductsList = $this->setUpNewSelectedProducts($subscriptionOrderItem, $newSelectedProductsList);
        if($settingNewSelectedProductsList instanceof JsonResponse) {
            return $settingNewSelectedProductsList;
        }

        return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::SUBSCRIPTION_ORDER_UPDATED, "id" => $subscriptionOrder->getId()], Response::HTTP_CREATED);
    }

    public function checkExistanceOfAllObligatoryCategories(SubscriptionOrder $subscriptionOrder, $newSelectedProductsList) {
        if(!$subscriptionOrder->getSubscription()->getProgram()->getProduct()->getProgram()->isStabilizationProgram()) {
            foreach ($subscriptionOrder->getSubscription()->getProgram()->getProduct()->getProgram()->getProgramCategoriesDetails() as $programCategoriesDetail) {
                if($programCategoriesDetail->isObligatory()) {
                    $foundProgramCategory = false;
                    foreach ($newSelectedProductsList as $selectedProduct) {
                        if($selectedProduct['taxon'] == $programCategoriesDetail->getTaxon()->getCode()) {
                            $foundProgramCategory = true;
                        }
                    }
                    if(!$foundProgramCategory) {
                        $this->displayingFlashMessage('error', 'ksante_subscription.flashes.unfulfilledProgramCategories');
                        return new JsonResponse(ApiResponses::UNFULFILLED_PROGRAM_CATEGORIES, Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        } else {
            foreach ($subscriptionOrder->getSubscription()->getStabilizationOptions() as $stabilizationOption) {
                $foundProgramCategory = false;
                foreach ($newSelectedProductsList as $selectedProduct) {
                    if($selectedProduct['taxon'] == $stabilizationOption->getStabilizationCategory()->getTaxonID()->getCode()) { //To update later
                        $foundProgramCategory = true;
                    }
                }
                if(!$foundProgramCategory) {
                    $this->displayingFlashMessage('error', 'ksante_subscription.flashes.unselectedStabilizationCategory');
                    return new JsonResponse(ApiResponses::UNSELECTED_STABILIZATION_CATEGORY.' id='.$stabilizationOption->getId(), Response::HTTP_BAD_REQUEST);
                }
            }
            foreach ($newSelectedProductsList as $selectedProduct) {
                $foundCategory = false;
                foreach ($subscriptionOrder->getSubscription()->getStabilizationOptions() as $stabilizationOption) {
                    if($stabilizationOption->getStabilizationCategory()->getTaxonID()->getCode() == $selectedProduct['taxon']) {
                        $foundCategory = true;
                    }
                }
                if(!$foundCategory) {
                    $this->displayingFlashMessage('error', 'ksante_subscription.flashes.unselectedCategoryInStabilizationOptions');
                    return new JsonResponse(ApiResponses::CATEGORY_NOT_SELECTED_IN_STABILIZATION_CATEGORIES, Response::HTTP_BAD_REQUEST);
                }
            }
        }
    }

    public function checkingTheSelectedProductsCountBasedOnTheProgramAndCategory(SubscriptionOrder $subscriptionOrder, $newSelectedProductsList, $isObligatory = 0) {
        /** @var Program $program */
        $program = $subscriptionOrder->getSubscription()->getProgram()->getProduct()->getProgram();

        $programCategoryDetailList = $this->getProgramCategories($program, $isObligatory);

        foreach ($newSelectedProductsList as $selectedProduct) {
            $selectedProductsCount = 0;
            $taxon = $this->taxonRepository->findOneBy(['code' => $selectedProduct['taxon']]);
            /** @var ProgramCategoriesDetail $programCategoryDetail */
            $programCategoryDetail = $this->programCategoryDetailRepository->findOneBy(['program' => $program->getId(), 'taxon' => $taxon->getId(), 'isObligatory' => $isObligatory]);
            if(empty($programCategoryDetail)) {
                $this->displayingFlashMessage('error', 'ksante_subscription.flashes.invalidSelectedCategory');
                return new JsonResponse(APIResponses::INVALID_SELECTED_CATEGORY.' available options are: '.$programCategoryDetailList, Response::HTTP_BAD_REQUEST);
            }
            foreach ($newSelectedProductsList as $otherSelectedProduct) {
                if($otherSelectedProduct['taxon'] == $selectedProduct['taxon']) {
                    $selectedProductsCount += $otherSelectedProduct['quantity'];
                }
            }
            if($selectedProductsCount < $programCategoryDetail->getMinimumNumberOfProducts() || $selectedProductsCount > $programCategoryDetail->getMaximumNumberOfProducts()) {
                $this->displayingFlashMessage('error', 'ksante_subscription.flashes.invalidProductQuantity');
                return new JsonResponse(APIResponses::INVALID_SELECTED_PRODUCTS_QUANTITY_TO_CATEGORY.$taxon->getCode(), Response::HTTP_BAD_REQUEST);
            }
        }
    }

    public function displayingFlashMessage($type, $message) {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add($type, [
            'message' => $message,
            'parameters' => [],
        ]);
    }

    //Removing the deleted subscription order item product based on the new list
    public function removeDeletedSelectedProducts(SubscriptionOrderItem $subscriptionOrderItem, $newSelectedProductsList) {
        foreach ($subscriptionOrderItem->getOrderItemDetails() as $orderItemDetail) {
            $foundOrderItemDetail = false;
            foreach ($newSelectedProductsList as $newSelectedItemDetail) {
                if($newSelectedItemDetail['id'] == $orderItemDetail->getId()) {
                    $foundOrderItemDetail = true;
                }
            }
            if(!$foundOrderItemDetail) {
                $this->subcriptionOrderItemDetailsManager->remove($orderItemDetail);
            }
        }

        $this->subcriptionOrderItemDetailsManager->flush();
    }

    //Setting up the new selected products by subscription order item, and checking the submitted list
    public function setUpNewSelectedProducts(SubscriptionOrderItem $subscriptionOrderItem, $newSelectedProductsList) {
        foreach ($newSelectedProductsList as $newSelectedProduct) {
            if(empty($newSelectedProduct['id']) /*|| $newSelectedProduct['id'] == ""*/) {
                if(!array_key_exists('variant', $newSelectedProduct) || !array_key_exists('taxon', $newSelectedProduct) || $newSelectedProduct['variant'] == "" || $newSelectedProduct['taxon'] == "") {
                    return new JsonResponse(APIResponses::NOT_ENOUGH_NECCESSARY_PARAMETERS_TO_UPDATE_SELECTEC_PRODUCTS, Response::HTTP_BAD_REQUEST);
                }
                /** @var SubscriptionOrderItemDetails $newItemDetail */
                $newItemDetail = $this->subcriptionOrderItemDetailsFactory->createNew();
            } else {
                /** @var SubscriptionOrderItemDetails $newItemDetail */
                $newItemDetail = $this->subcriptionOrderItemDetailsRepository->findOneBy(['id' => $newSelectedProduct['id']]);
                if(empty($newItemDetail) || $newItemDetail->getOrderItem()->getId() != $subscriptionOrderItem->getId()) {
                    return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_ITEM_DETAIL_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
                }
            }
            if(!array_key_exists('quantity', $newSelectedProduct) || $newSelectedProduct['quantity'] == "" || $newSelectedProduct['quantity'] == 0) {
                $newSelectedProduct['quantity'] = 1;
            }
            $newItemDetail->setQuantity($newSelectedProduct['quantity']);
            $newVariant = $this->productVariantRepository->findOneBy(['code' => $newSelectedProduct['variant']]);
            if(empty($newVariant)) {
                return new JsonResponse(APIResponses::PRODUCT_VARIANT_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
            }

            $newTaxon = $this->taxonRepository->findOneBy(['code' => $newSelectedProduct['taxon']]);
            if(empty($newTaxon)) {
                return new JsonResponse(APIResponses::TAXON_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
            }

            $newItemDetail->setVariant($newVariant);
            $newItemDetail->setTaxon($newTaxon);
            $newItemDetail->setOrderItem($subscriptionOrderItem);
            $subscriptionOrderItem->addOrderItemDetail($newItemDetail);

            $this->subcriptionOrderItemDetailsManager->persist($newItemDetail);
            $this->subcriptionOrderItemManager->persist($subscriptionOrderItem);
        }

        $this->subcriptionOrderItemDetailsManager->flush();
        $this->subcriptionOrderItemManager->flush();
    }

    //Getting subscription order by ID
    public function getSubscriptionOrderById($id) {
        return $this->subscriptionOrderRepository->findOneBy(['id' => $id]);
    }

    //Updating the selected options by subscription order ID
    public function updateSubscriptionOrderSelectedOptions($subscriptionOrderId, $newSelectedOptionsList) {
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->subscriptionOrderRepository->findOneBy(['id' => $subscriptionOrderId]);
        if(empty($subscriptionOrder)) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        if($subscriptionOrder->isValidated()) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_IS_ALREADY_VALIDATED, Response::HTTP_BAD_REQUEST);
        }

        $optionsCountChecking = $this->checkingTheSelectedProductsCountBasedOnTheProgramAndCategory($subscriptionOrder, $newSelectedOptionsList);
        if($optionsCountChecking instanceof JsonResponse) {
            return $optionsCountChecking;
        }

        $this->removeDeletedSelectedOptions($subscriptionOrder, $newSelectedOptionsList);

        $settingNewSelectedOptionsList = $this->setUpNewSelectedOptions($subscriptionOrder, $newSelectedOptionsList);
        if($settingNewSelectedOptionsList instanceof JsonResponse) {
            return $settingNewSelectedOptionsList;
        }

        return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::SUBSCRIPTION_ORDER_UPDATED, "id" => $subscriptionOrder->getId()], Response::HTTP_CREATED);
    }

    public function getProgramCategories(Program $program, $isObligatory = 0) {
        $categoriesList = "";
        $categoriesByProgram = $this->programCategoryDetailRepository->findBy(['program' => $program->getId(), 'isObligatory' => $isObligatory]);
        foreach ($categoriesByProgram as $categoryProgram) {
            $categoriesList  .= ", ".$categoryProgram->getTaxon()->getCode();
        }

        return $categoriesList;
    }

    //Setting up the new selected options by subscription order, and checking the submitted list
    public function setUpNewSelectedOptions(SubscriptionOrder $subscriptionOrder, $newSelectedOptionsList) {
        foreach ($newSelectedOptionsList as $newSelectedOption) {
            if(empty($newSelectedOption['id']) /*|| $newSelectedProduct['id'] == ""*/) {
                if(!array_key_exists('variant', $newSelectedOption) || !array_key_exists('taxon', $newSelectedOption) || $newSelectedOption['variant'] == "" || $newSelectedOption['taxon'] == "") {
                    return new JsonResponse(APIResponses::NOT_ENOUGH_NECCESSARY_PARAMETERS_TO_UPDATE_SELECTED_OPTIONS, Response::HTTP_BAD_REQUEST);
                }
                /** @var SubscriptionOrderItem $newItem */
                $newItem = $this->subcriptionOrderItemFactory->createNew();
            } else {
                /** @var SubscriptionOrderItem $newItem */
                $newItem = $this->subcriptionOrderItemRepository->findOneBy(['id' => $newSelectedOption['id']]);
                if(empty($newItem)) {
                    return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_ITEM_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
                }
            }
            if(!array_key_exists('quantity', $newSelectedOption) || $newSelectedOption['quantity'] == "" || $newSelectedOption['quantity'] == 0) {
                $newSelectedOption['quantity'] = 1;
            }
            $newItem->setQuantity($newSelectedOption['quantity']);
            $newVariant = $this->productVariantRepository->findOneBy(['code' => $newSelectedOption['variant']]);
            if(empty($newVariant)) {
                return new JsonResponse(APIResponses::PRODUCT_VARIANT_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
            }

            $newTaxon = $this->taxonRepository->findOneBy(['code' => $newSelectedOption['taxon']]);
            if(empty($newTaxon)) {
                return new JsonResponse(APIResponses::TAXON_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
            }

            $newItem->setVariant($newVariant);
            $newItem->setTaxon($newTaxon);
            $newItem->setIsOption(true);
            $newItem->setOrder($subscriptionOrder);
            $subscriptionOrder->addItem($newItem);

            $this->subcriptionOrderItemManager->persist($newItem);
            $this->subcriptionOrderManager->persist($subscriptionOrder);
        }

        $this->subcriptionOrderItemManager->flush();
        $this->subcriptionOrderManager->flush();
    }

    //Removing the deleted subscription order item options based on the new list
    public function removeDeletedSelectedOptions(SubscriptionOrder $subscriptionOrder, $newSelectedOptionsList) {
        foreach ($subscriptionOrder->getOptionsItems() as $optionItem) {
            $foundOrderItem = false;
            foreach ($newSelectedOptionsList as $newSelectedOption) {
                if($newSelectedOption['id'] == $optionItem->getId()) {
                    $foundOrderItem = true;
                }
            }

            if(!$foundOrderItem) {
                $this->subcriptionOrderItemManager->remove($optionItem);
            }
        }

        $this->subcriptionOrderItemManager->flush();
    }

    //Updating the selected options by subscription order id and by giving the list of the new selected products from the FO
    public function updateSubscriptionOrderSelectedOptionsFromFront($parameters) {
        if (!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return new JsonResponse(APIResponses::AUTHENTICATION_REQUIRED, Response::HTTP_BAD_REQUEST);
        }
        if(!array_key_exists('id', $parameters)) {
            return new JsonResponse(APIResponses::UNNPROVIDED_SUBSCRIPTION_ORDER_ID, Response::HTTP_BAD_REQUEST);
        }
        if(!array_key_exists('newSelectedOptions', $parameters)) {
            return new JsonResponse(APIResponses::UNNPROVIDED_NEW_SELECTED_OPTIONS_LIST, Response::HTTP_BAD_REQUEST);
        }
        return $this->updateSubscriptionOrderSelectedOptions($parameters['id'], $parameters['newSelectedOptions']);
    }

    public function calculateTotalProductsQuantity(SubscriptionOrderItem $subscriptionOrderItem) {
        $totalQuantity = 0;
        foreach ($subscriptionOrderItem->getOrderItemDetails() as $orderItemDetail) {
            $totalQuantity += $orderItemDetail->getQuantity();
        }

        return $totalQuantity;
    }

    public function calculateTotalOptionsQuantity(SubscriptionOrder $subscriptionOrder) {
        $totalQuantity = 0;
        foreach ($subscriptionOrder->getItems() as $item) {
            if($item->isOption()) {
                $totalQuantity += $item->getQuantity();
            }
        }

        return $totalQuantity;
    }

    public function updateSubscriptionOrderCouponCode($parameters) {
        if(!array_key_exists('id', $parameters) || !array_key_exists('couponCode', $parameters)) {
            $this->displayingFlashMessage('error', 'ksante_subscription.flashes.notEnoughParametersToUpdateSubscriptionOrderCouponCode');
            return 0;
        }
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->subscriptionOrderRepository->findOneBy(['id' => $parameters['id']]);

        if(empty($subscriptionOrder)) {
            $this->displayingFlashMessage('error', 'ksante_subscription.flashes.subscriptionOrderDoesNotExist');
            return 0;
        }
        $promotionCoupon = null;
        if($parameters['couponCode'] != "") {
            $promotionCoupon = $this->promotionCouponRepository->findOneBy(['code' => $parameters['couponCode']]);
        }

        if((empty($promotionCoupon) && $parameters['couponCode'] != "")) {
            $this->displayingFlashMessage('error', 'ksante_subscription.flashes.promotionCouponDoesNotExist');
            return 0;
        }

        $subscriptionOrder->setCouponCode($parameters['couponCode']);

        $this->subcriptionOrderManager->persist($subscriptionOrder);
        $this->subcriptionOrderManager->flush();
    }
}
