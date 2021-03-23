<?php

namespace Ksante\SubscriptionPlugin\Service;

use Ksante\SubscriptionPlugin\Entity\OrderItemDetails;
use Ksante\SubscriptionPlugin\Entity\Program;
use Ksante\SubscriptionPlugin\Entity\ProgramCategoriesDetail;
use Ksante\SubscriptionPlugin\Entity\Subscription;
use Ksante\SubscriptionPlugin\Entity\SubscriptionLog;
use Ksante\SubscriptionPlugin\Entity\SubscriptionOrder;
use Ksante\SubscriptionPlugin\State\OrderStates;
use Ksante\SubscriptionPlugin\State\SubscriptionStates;
use Nette\Utils\DateTime;
use Proxies\__CG__\Sylius\Component\Core\Model\Address;
use Sylius\Bundle\OrderBundle\NumberGenerator\OrderNumberGeneratorInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderShippingStates;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Resource\Generator\RandomnessGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sylius\Component\Core\Model\Shipment;
use Symfony\Component\HttpFoundation\Response;
use Ksante\SubscriptionPlugin\Controller\ApiResponses;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderService
{
    /** @var ContainerInterface */
    protected $container;

    /** @var SessionInterface */
    protected $session;

    /** @var OrderNumberGeneratorInterface */
    protected $numberGenerator;

    /** @var RandomnessGeneratorInterface */
    private $generator;

    protected $stabilizationOptionsService;

    protected $orderClassName;

    protected $programRepository;
    protected $subscriptionRepository;
    protected $subscriptionOrderRepository;
    protected $productVariantRepository;
    protected $productRepository;
    protected $stabilizationOptionsRepository;
    protected $programCategoryDetailRepository;
    protected $taxonRepository;
    protected $customerRepository;
    protected $promotionCouponRepository;

    protected $orderFactory;
    protected $orderItemFactory;
    protected $orderItemDetailsFactory;
    protected $subscriptionFactory;
    protected $subscriptionOrderFactory;
    protected $subscriptionOrderItemFactory;
    protected $subscriptionOrderItemDetailsFactory;
    protected $addressFactory;
    protected $stabilizationOptionsFactory;
    protected $subscriptionLogFactory;
    protected $paymentFactory;
    protected $shipmentFactory;

    protected $orderManager;
    protected $orderItemManager;
    protected $orderItemDetailsManager;
    protected $programManager;
    protected $subscriptionManager;
    protected $subscriptionOrderManager;
    protected $subscriptionOrderItemManager;
    protected $subscriptionOrderItemDetailsManager;
    protected $addressManager;
    protected $stabilizationOptionsManager;
    protected $subscriptionLogManager;
    protected $paymentManager;
    protected $shipmentManager;

    protected $orderItemQuantityModifier;

    public function __construct(ContainerInterface $container, OrderNumberGeneratorInterface $numberGenerator, $orderClassName, SessionInterface $session, RandomnessGeneratorInterface $generator) {
        $this->container = $container;
        $this->session = $session;
        $this->numberGenerator = $numberGenerator;
        $this->orderClassName = $orderClassName;
        $this->stabilizationOptionsService = $this->container->get('ksante_subscription_plugin.service.stabilization_options_service');

        //Setting the used repositories
        $this->programRepository = $this->container->get('ksante_subscription_plugin.repository.program');
        $this->subscriptionRepository = $this->container->get('ksante_subscription.repository.subscription');
        $this->subscriptionOrderRepository = $this->container->get('ksante_subscription.repository.subscription_order');
        $this->productRepository = $this->container->get('sylius.repository.product');
        $this->productVariantRepository = $this->container->get('sylius.repository.product_variant');
        $this->stabilizationOptionsRepository = $this->container->get('ksante_subscription.repository.stabilization_options');
        $this->programCategoryDetailRepository = $this->container->get('ksante_subscription.repository.program_categories_detail');
        $this->taxonRepository = $this->container->get('sylius.repository.taxon');
        $this->customerRepository = $this->container->get('sylius.repository.customer');
        $this->promotionCouponRepository = $this->container->get('sylius.repository.promotion_coupon');

        //Setting the used factories
        $this->orderFactory = $this->container->get('sylius.factory.order');
        $this->orderItemFactory = $this->container->get('sylius.factory.order_item');
        $this->orderItemDetailsFactory = $this->container->get('ksante_subscription.factory.order_item_details');
        $this->subscriptionFactory = $this->container->get('ksante_subscription.factory.subscription');
        $this->subscriptionOrderFactory = $this->container->get('ksante_subscription.factory.subscription_order');
        $this->subscriptionOrderItemFactory = $this->container->get('ksante_subscription.factory.subscription_order_item');
        $this->subscriptionOrderItemDetailsFactory = $this->container->get('ksante_subscription.factory.subscription_order_item_details');
        $this->addressFactory = $this->container->get('sylius.factory.address');
        $this->stabilizationOptionsFactory = $this->container->get('ksante_subscription.factory.stabilization_options');
        $this->subscriptionLogFactory = $this->container->get('ksante_subscription.factory.subscription_logs');
        $this->paymentFactory = $this->container->get('sylius.factory.payment');
        $this->shipmentFactory = $this->container->get('sylius.factory.shipment');

        //Setting the used managers
        $this->orderManager = $this->container->get('sylius.manager.order');
        $this->orderItemManager = $this->container->get('sylius.manager.order_item');
        $this->orderItemDetailsManager = $this->container->get('ksante_subscription.manager.order_item_details');
        $this->programManager = $this->container->get('ksante_subscription_plugin.manager.program');
        $this->subscriptionManager = $this->container->get('ksante_subscription.manager.subscription');
        $this->subscriptionOrderManager = $this->container->get('ksante_subscription.manager.subscription_order');
        $this->subscriptionOrderItemManager = $this->container->get('ksante_subscription.manager.subscription_order_item');
        $this->subscriptionOrderItemDetailsManager = $this->container->get('ksante_subscription.manager.subscription_order_item_details');
        $this->addressManager = $this->container->get('sylius.manager.address');
        $this->stabilizationOptionsManager = $this->container->get('ksante_subscription.manager.stabilization_options');
        $this->subscriptionLogManager = $this->container->get('ksante_subscription.manager.subscription_logs');
        $this->paymentManager = $this->container->get('sylius.manager.payment');
        $this->shipmentManager = $this->container->get('sylius.manager.shipment');

        //Setting the other used services
        $this->orderItemQuantityModifier = $this->container->get('sylius.order_item_quantity_modifier');

        $this->generator = $generator;
    }

    //Creating the first customer order along with setting the selected product and creating the subscription
    public function createOrderAlongWithSubscription($parameters)
    {
        if (!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            //throw new AccessDeniedException('You have to be registered user to access this section.');
        }

        //Checking the presence of all the required parameters
        $parametersChecking = $this->checkCreateFirstOrderParameters($parameters);
        if($parametersChecking instanceof JsonResponse) {
            return $parametersChecking;
        }
        $selectedProducts = $parameters['selectedProducts'];

        $customer = $this->customerRepository->findOneBy(['id' => 30]);

        //Setting the basic order data
        /** @var Order $order */
        $order = $this->container->get('sylius.context.cart')->getCart();
        $order->setCustomer($customer);

        /*if(!$order->getCustomer()->getUser()->isEnabled()) {
            return new JsonResponse(APIResponses::DISABLED_CUSTOMER, Response::HTTP_BAD_REQUEST);
        }*/

        /** @var Product $program */
        $program = $this->programRepository->findOneBy(['id' => $parameters['program']])->getProduct();

        if(empty($program)) {
            return new JsonResponse(APIResponses::PROGRAM_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        $stabilizationOptions = $this->getStabilizationOptions($program, $parameters, $order);
        if($stabilizationOptions instanceof JsonResponse)  {
            return $stabilizationOptions;
        }

        //Checking the presence of all the program's obligatory categories
        $checkingTheSelectedCategories = $this->checkingTheSelectedCategories($program, $selectedProducts, $stabilizationOptions);
        if($checkingTheSelectedCategories instanceof JsonResponse) {
            return $checkingTheSelectedCategories;
        }

        //Checking the selected products count compared to the maximum number of selected products based on the categories
        $checkingTheSelectedProductsCount = $this->checkingSelectedProductsCount($program, $selectedProducts);
        if($checkingTheSelectedProductsCount instanceof JsonResponse) {
            return $checkingTheSelectedProductsCount;
        }

        /*$checkIfTheCustomerHasAlreadyAnActiveSubscription = $subscriptionRepository->findActiveSubscriptionsByCustomer($user->getId(), $this->orderClassName);
        if(!empty($checkIfTheCustomerHasAlreadyAnActiveSubscription)) {
            return new JsonResponse(APIResponses::EXISTENCE_ACTIVE_SUBSCRIPTION, Response::HTTP_BAD_REQUEST);
        }*/

        /*$checkIfTheCustomerHasAlreadyAFulfilledSubscription = $this->subscriptionRepository->ifCustomerHasSubscription($user->getId(), $this->orderClassName);
        if(empty($checkIfTheCustomerHasAlreadyAFulfilledSubscription) || !$this->checkIfUserHasAFulfilledOrders($checkIfTheCustomerHasAlreadyAFulfilledSubscription)) {
            return new JsonResponse(APIResponses::FULFULLED_SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }*/

        $order->setChannel($this->container->get('sylius.context.channel')->getChannel());
        $order->setNumber($this->numberGenerator->generate($order));
        $order->setCheckoutCompletedAt(new \DateTime());

        $stabilizationOptions = $this->getStabilizationOptions($program, $parameters, $order);
        if($stabilizationOptions instanceof JsonResponse)  {
            return $stabilizationOptions;
        }

        //Setting the other selected products besides the program
        if(!empty($parameters['otherProducts'])) {
            $settingTheOtherSelectedProduct = $this->settingOtherSelectedProduct($order, $parameters['otherProducts']);
            if($settingTheOtherSelectedProduct instanceof JsonResponse) {
                return $settingTheOtherSelectedProduct;
            }
        }

        //Setting the program as a product along with his selected list of products
        $settingTheProgramAndItsSelectedProducts = $this->settingProgramAndItsSelectedProducts($order, $program, $selectedProducts, $stabilizationOptions);
        if($settingTheProgramAndItsSelectedProducts instanceof JsonResponse) {
            return $settingTheProgramAndItsSelectedProducts;
        }

        //Creating the subscription
        $this->settingSubscriptionData($program, $order, $stabilizationOptions);

        $order->setTokenValue($order->getTokenValue() ?? $this->generator->generateUriSafeString(10));

        $this->programManager->flush();
        $this->orderManager->persist($order);

        $this->orderItemManager->flush();
        $this->orderItemDetailsManager->flush();
        $this->orderManager->flush();
        $this->subscriptionLogManager->flush();
        $this->subscriptionManager->flush();

        return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::ORDER_CREATED, "id" => $order->getTokenValue()], Response::HTTP_CREATED);
    }

    //Checking if the user has fulfilled all the orders by subscription based on the subscription periodicity
    public function checkIfUserHasAFulfilledOrders($subscriptions) {
        foreach ($subscriptions as $subscription) {
            if($subscription->countOrders() >= $subscription->getProgram()->getProduct()->getProgram()->getMinimumSubscription()) {
                return true;
            }
        }
        return false;
    }

    //Getting the stabilization options
    public function getStabilizationOptions($program, $parameters, OrderInterface $order) {
        $stabilizationOptions = [];
        if($program->getProgram()->isStabilizationProgram()) {
            foreach ($parameters['stabilizationOptions'] as $stabilizationOptionID) {
                $stabilizationOptions [] = $this->stabilizationOptionsRepository->findOneBy(['id' => $stabilizationOptionID]);
            }
            /*$checkingChosenStabilizationOptions = $this->checkChosenStabilizationOptionsByCustomerHistory($stabilizationOptions, $order->getCustomer(), $order);
            if($checkingChosenStabilizationOptions instanceof JsonResponse) {
                return $checkingChosenStabilizationOptions;
            }*/
        }
        return $stabilizationOptions;
    }

    public function checkChosenStabilizationOptionsByCustomerHistory($selectedStabilizationOptions, CustomerInterface $customer, OrderInterface $order) {
        $stabilizationOptionsBasedOnUser = $this->stabilizationOptionsService->getStabilizationOptionsByCustomerChannelCurrency($customer, $order->getChannel(), $order->getCurrencyCode());
        foreach ($selectedStabilizationOptions as $selectedStabilizationOption) {
            if(!in_array($selectedStabilizationOption, $stabilizationOptionsBasedOnUser)) {
                return new JsonResponse(APIResponses::INCORRECT_STABILIZATION_OPTION, Response::HTTP_BAD_REQUEST);
            }
        }
    }

    //Checking the presence of all the required parameters
    public function checkCreateFirstOrderParameters(array $parameters) {
        if (!array_key_exists('program', $parameters)) {
            return new JsonResponse(APIResponses::UNPROVIDED_PROGRAM, Response::HTTP_BAD_REQUEST);
        }
        $program = $this->programRepository->findOneBy(['id' => $parameters['program']])->getProduct();
        if(empty($program) || empty($program->getProgram())) {
            return new JsonResponse(APIResponses::PROGRAM_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }
        if($program->getProgram()->isStabilizationProgram() && !array_key_exists('stabilizationOptions', $parameters)) {
            return new JsonResponse(APIResponses::UNPROVIDED_STABILIZATION_OPTIONS, Response::HTTP_BAD_REQUEST);
        }

        if (!array_key_exists('selectedProducts', $parameters)) {
            return new JsonResponse(APIResponses::UNPROVIDED_SELECTED_PRODUCTS, Response::HTTP_BAD_REQUEST);
        }
        foreach ($parameters['selectedProducts'] as $selectedProduct) {
            if (!array_key_exists('category', $selectedProduct) || !array_key_exists('products', $selectedProduct)) {
                return new JsonResponse(ApiResponses::UNPROVIDED_CATEGORY_ID, Response::HTTP_BAD_REQUEST);
            } else {
                foreach ($selectedProduct['products'] as $product) {
                    if (!array_key_exists('id', $product)) {
                        return new JsonResponse(ApiResponses::UNPROVIDED_PRODUCT, Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        }
        if (array_key_exists('otherProducts', $parameters)) {
            foreach ($parameters['otherProducts'] as $otherProduct) {
                if (!array_key_exists('id', $otherProduct)) {
                    return new JsonResponse(APIResponses::UNPROVIDED_PRODUCT, Response::HTTP_BAD_REQUEST);
                }
            }
        }

    }

    //Checking the selected products count compared to the maximum number of selected products
    public function checkingSelectedProductsCount(ProductInterface $program, &$selectedProducts) {
        foreach ($selectedProducts as $selectedProduct) {
            $selectedProductsCount = 0;
            $programCategoryDetail = $this->programCategoryDetailRepository->findOneBy(['program' => $program->getProgram()->getId(), 'taxon' => $selectedProduct['category']]);
            if(empty($programCategoryDetail)) {
                return new JsonResponse(ApiResponses::INVALID_SELECTED_CATEGORY. ', id = '.$selectedProduct['category'], Response::HTTP_BAD_REQUEST);
            } else {
                foreach ($selectedProduct['products'] as $product) {
                    if (array_key_exists('quantity', $product)) {
                        $selectedProductsCount += $product['quantity'];
                    }
                }
            }
            if(($selectedProductsCount < $programCategoryDetail->getMinimumNumberOfProducts() || $selectedProductsCount > $programCategoryDetail->getMaximumNumberOfProducts())) {
                return new JsonResponse(ApiResponses::INVALID_SELECTED_PRODUCTS_QUANTITY_TO_CATEGORY.$selectedProduct['category'], Response::HTTP_BAD_REQUEST);
            }
        }
    }

    //Checking the presence of all the program's obligatory categories
    public function checkingTheSelectedCategories(ProductInterface $program, $selectedProducts, $stabilizationOptions) {
        if(!$program->getProgram()->isStabilizationProgram()) {
            foreach ($program->getProgram()->getProgramCategoriesDetails() as $programCategoriesDetail) {
                if($programCategoriesDetail->isObligatory()) {
                    $foundProgramCategory = false;
                    foreach ($selectedProducts as $selectedProduct) {
                        if($selectedProduct['category'] == $programCategoriesDetail->getTaxon()->getId()) {
                            $foundProgramCategory = true;
                        }
                    }
                    if(!$foundProgramCategory) {
                        return new JsonResponse(ApiResponses::UNFULFILLED_PROGRAM_CATEGORIES, Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        } else {
            $foundProgramCategory = false;
            foreach ($stabilizationOptions as $stabilizationOption) {
                foreach ($selectedProducts as $selectedProduct) {
                    if($selectedProduct['category'] == $stabilizationOption->getStabilizationCategory()->getTaxonID()->getId()) { //To update later
                        $foundProgramCategory = true;
                    }
                }
                if(!$foundProgramCategory) {
                    return new JsonResponse(ApiResponses::UNSELECTED_STABILIZATION_CATEGORY.' id='.$stabilizationOption->getId(), Response::HTTP_BAD_REQUEST);
                }
            }
            /*$foundCategory = false;
            foreach ($selectedProducts as $selectedProduct) {
                foreach ($stabilizationOptions as $stabilizationOption) {
                    if($stabilizationOption->getStabilizationCategory()->getTaxonID()->getId() == $selectedProduct['category']) {
                        $foundCategory = true;
                    }
                }
                if(!$foundCategory) {
                    return new JsonResponse(ApiResponses::CATEGORY_NOT_SELECTED_IN_STABILIZATION_CATEGORIES, Response::HTTP_BAD_REQUEST);
                }
            }*/
        }
    }

    //Setting the other selected products besides the program
    public function settingOtherSelectedProduct(&$order, $otherProducts) {
        foreach ($otherProducts as $otherProduct) {
            /** @var OrderItem $orderItem */
            $orderItem = $this->orderItemFactory->createNew();
            $product = $this->productVariantRepository->findOneBy(['id' => $otherProduct['id']]);
            if(empty($product)) {
                return new JsonResponse(APIResponses::PRODUCT_NOT_EXIST.', id='.$otherProduct['id'], Response::HTTP_BAD_REQUEST);
            }
            $orderItem->setVariant($product);
            $price = $product->getVariants()[0]->getChannelPricingForChannel($this->container->get('sylius.context.channel')->getChannel())->getPrice();
            $orderItem->setUnitPrice($price);
            $orderItem->setOrder($order);
            $this->orderItemQuantityModifier->modify($orderItem, empty($otherProduct['quantity']) ? 1 : $otherProduct['quantity']);

            $this->orderItemManager->persist($orderItem);
        }
    }

    //Setting the program as a product along with his selected list of products
    public function settingProgramAndItsSelectedProducts(&$order, &$program, $selectedProducts, $stabilizationOptions) {
        /** @var OrderItem $orderItemProgram */
        $orderItemProgram = $this->orderItemFactory->createNew();
        $orderItemProgram->setVariant($program->getVariants()[0]);
        $this->orderItemQuantityModifier->modify($orderItemProgram, 1);
        $orderItemProgram->setVariant($program->getVariants()[0]);
        $this->setStabilizationOptionsPrice($program, $orderItemProgram, $stabilizationOptions);

        foreach ($selectedProducts as $selectedProductWithCategory) {
            $taxon = $this->taxonRepository->findOneBy(['id' => $selectedProductWithCategory['category']]);
            /** @var ProgramCategoriesDetail $programCategoryDetail */
            $programCategoryDetail = $this->programCategoryDetailRepository->findOneBy(['program' => $program->getProgram()->getId(), 'taxon' => $selectedProductWithCategory['category']]);
            foreach ($selectedProductWithCategory['products'] as $selectedProduct) {
                $product = $this->productVariantRepository->findOneBy(['id' => $selectedProduct['id']]);  //Change here to product variant
                if(empty($product)) {
                    return new JsonResponse(APIResponses::PRODUCT_NOT_EXIST.', id='.$selectedProduct['id'], Response::HTTP_BAD_REQUEST);
                }
                if($programCategoryDetail->isObligatory()) {
                    /** @var OrderItemDetails $orderItemDetail */
                    $orderItemDetail = $this->orderItemDetailsFactory->createNew();
                    $orderItemDetail->setVariant($product);
                    $orderItemDetail->setQuantity(empty($selectedProduct['quantity']) ? 1: $selectedProduct['quantity']);
                    $orderItemDetail->setOrderItem($orderItemProgram);
                    $orderItemDetail->setCreatedAt(new \DateTime());
                    $orderItemDetail->setTaxon($taxon);

                    $orderItemProgram->addOrderItemDetail($orderItemDetail);
                    $this->orderItemDetailsManager->persist($orderItemDetail);
                } else {
                    $orderItemOption = $this->orderItemFactory->createNew();
                    $orderItemOption->setVariant($product);
                    $orderItemOption->setIsOption(true);
                    $orderItemOption->setTaxon($taxon);
                    $this->orderItemQuantityModifier->modify($orderItemOption, empty($selectedProduct['quantity']) ? 1: $selectedProduct['quantity']);
                    $this->orderItemManager->persist($orderItemOption);
                    $order->addItem($orderItemOption);
                }
            }
        }
        $order->addItem($orderItemProgram);
        $this->container->get('sylius.order_processing.order_processor')->process($order);
    }

    //Getting the stabilization program price based on the selected options
    public function setStabilizationOptionsPrice($program, OrderItem $orderItem, $stabilizationOptions) {
        $price = 0;
        if(empty($stabilizationOptions)) {
            $price = $program->getVariants()[0]->getChannelPricingForChannel($this->container->get('sylius.context.channel')->getChannel())->getPrice();
        } else {
            foreach ($stabilizationOptions as $stabilizationOption) {
                if(!empty($stabilizationOption)) {
                    $price += $stabilizationOption->getPrice();
                }
            }
        }
        /** @var Program $programDetail */
        $programDetail = $program->getProgram();
        $programDetail->setProgramPrice($price);

        $this->programManager->persist($programDetail);
        $this->programManager->flush();

        $orderItem->setUnitPrice($price);
    }

    //Creating the subscription
    public function settingSubscriptionData(&$program, &$order, $stabilizationOptions) {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionFactory->createNew();
        $subscription->setCustomer($order->getCustomer());
        $subscription->setProgram($program->getVariants()[0]);
        $subscription->setFirstOrderDate(new \DateTime());
        $subscription->setLastOrderDate(new \DateTime());
        $subscription->setState(SubscriptionStates::ON_GOING);
        $subscription->setChannel($this->container->get('sylius.context.channel')->getChannel());

        foreach ($stabilizationOptions as $stabilizationOption) {
            if(!empty($stabilizationOption)) {
                $subscription->addStabilizationOption($stabilizationOption); //TO CHANGE LATER ON (ITS FOR TESTING PURPOSES)
            }
        }
        //$this->container->get('sylius.order_processing.order_processor')->process($order);

        //Creating the first default subscription log (the created status)
        /** @var SubscriptionLog $subscriptionLog */
        $subscriptionLog = $this->subscriptionLogFactory->createNew();
        $subscriptionLog->setNewStatus(SubscriptionStates::CREATED);
        $subscriptionLog->setSubscription($subscription);
        $subscriptionLog->setCustomer($order->getCustomer());
        $subscriptionLog->setCreatedAt(new DateTime());

        $this->subscriptionLogManager->persist($subscriptionLog);

        $order->setSubscription($subscription);
        $this->orderManager->persist($order);

        $subscription->addOrder($order);
        $this->subscriptionManager->persist($subscription);
    }

    public function setUpNewSubscriptionOrder(Subscription $subscription) {
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->subscriptionOrderRepository->findOneBy(['subscription' => $subscription->getId(), 'isValidated' => 0]);

        if(empty($subscriptionOrder) && $subscription->getLatestOrder()->getPaymentState() == OrderStates::STATE_PAID) {
            /** @var Order $latestOrder */
            $latestOrder = $subscription->getLatestOrder();

            /** @var OrderInterface $newOrder */
            $newOrder = $this->subscriptionOrderFactory->createNew();

            //Setting up primary order data
            $this->setUpPrimaryOrderData($latestOrder, $newOrder);

            //Setting up the expected delivery date
            $newOrder->setExpectedDeliveryDate($this->generateNewExpectedDeliveryDate());

            //Setting up order's shipping addresses
            $this->setUpNewOrderShippingAddresses($latestOrder, $newOrder);

            //Setting up order's program item with its selected products
            $this->setUpNewOrderSelectedProducts($latestOrder, $newOrder, true);

            $newOrder->setOrder($latestOrder);

            //Setting the new changes in the database
            $this->subscriptionOrderManager->persist($newOrder);
            $subscription->addSubscriptionOrder($newOrder);
            $this->subscriptionManager->persist($subscription);

            $this->addressManager->flush();
            $this->subscriptionOrderItemDetailsManager->flush();
            $this->subscriptionOrderItemManager->flush();
            $this->subscriptionOrderManager->flush();
            $this->subscriptionManager->flush();

            return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::ORDER_CREATED, "id" => $newOrder->getId()], Response::HTTP_CREATED);
        }
    }

    //Generating the subscription order after shipping a subscription order
    public function generateNewSubscriptionOrder(Shipment $shipment) {
        //Checking the default requirements to generate new subscription order
        $shipmentChecking = $this->checkShipmentDataBeforeGeneratingNewSubscriptionOrder($shipment);
        if($shipmentChecking instanceof JsonResponse) {
            return $shipmentChecking;
        }
        /** @var Order $latestOrder */
        $latestOrder = $shipment->getOrder()->getSubscription()->getLatestOrder();

        /** @var Subscription $subscription */
        $subscription = $latestOrder->getSubscription();

        /** @var OrderInterface $newOrder */
        $newOrder = $this->subscriptionOrderFactory->createNew();

        //Setting up primary order data
        $this->setUpPrimaryOrderData($latestOrder, $newOrder);

        //Setting up the expected delivery date
        $newOrder->setExpectedDeliveryDate($this->generateNewExpectedDeliveryDate());

        //Setting up order's shipping addresses
        $this->setUpNewOrderShippingAddresses($latestOrder, $newOrder);

        //Setting up order's program item with its selected products
        $this->setUpNewOrderSelectedProducts($latestOrder, $newOrder, true);

        $newOrder->setOrder($latestOrder);

        //Removing other existing Subscription Orders
        //$this->removingOtherExistingSubscriptionOrders($subscription);

        //Setting the new changes in the database
        $this->subscriptionOrderManager->persist($newOrder);
        $subscription->addSubscriptionOrder($newOrder);
        $this->subscriptionManager->persist($subscription);

        $this->addressManager->flush();
        $this->subscriptionOrderItemDetailsManager->flush();
        $this->subscriptionOrderItemManager->flush();
        $this->subscriptionOrderManager->flush();
        $this->subscriptionManager->flush();

        return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::ORDER_CREATED, "id" => $newOrder->getId()], Response::HTTP_CREATED);
    }

    //Removing the other existing subscription (to update later on by updating the validation flag)
    public function removingOtherExistingSubscriptionOrders(Subscription $subscription) {
        foreach ($subscription->getSubscriptionOrders() as $subscriptionOrder) {
            $subscription->removeSubscriptionOrder($subscriptionOrder);
            $this->subscriptionOrderManager->remove($subscriptionOrder);
        }
    }

    //Checking whether or not create a subscription order based on the shipment data (order, the existence of a subscription, the subscription periodicity, etc.)
    public function checkShipmentDataBeforeGeneratingNewSubscriptionOrder(Shipment $shipment) {
        //Checking the existance of a subscription
        if(empty($shipment->getOrder()->getSubscription()) || $shipment->getOrder()->isRegularizationOrder()) {
            return new JsonResponse(APIResponses::CANT_AUTO_GENERATE_ORDER, Response::HTTP_BAD_REQUEST);
        }
        //Checking the subscription state
        if(($shipment->getOrder()->getSubscription()->getState() != SubscriptionStates::ON_GOING) && ($shipment->getOrder()->getSubscription()->getState() != SubscriptionStates::AUTO_PAUSED)) {
            return new JsonResponse(APIResponses::UNACTIVE_SUBSCRIPTION, Response::HTTP_BAD_REQUEST);
        }

        /** @var Order $latestOrder */
        $latestOrder = $shipment->getOrder()->getSubscription()->getLatestOrder();

        /** @var Subscription $subscription */
        $subscription = $latestOrder->getSubscription();

        $checkingTheSubscriptionPeriodicity = $this->checkSubscriptionPeriodicity($subscription);
        if($checkingTheSubscriptionPeriodicity instanceof JsonResponse) {
            return $checkingTheSubscriptionPeriodicity;
        }
    }

    //Checking a subscription periodicity
    public function checkSubscriptionPeriodicity(Subscription &$subscription) {
        $subscriptionNumberOfOrdersBasedOnPeriodicity = 0;
        foreach ($subscription->getOrders()[0]->getItems() as $item) {
            if(!empty($item->getVariant()->getProduct()->getProgram())) {
                if(empty($item->getVariant()->getProduct()->getProgram()->getMaximumSubscription())) {
                    $subscriptionNumberOfOrdersBasedOnPeriodicity = -1;
                    break;
                } else {
                    if($item->getVariant()->getProduct()->getProgram()->getPeriodicity() == "28") {
                        $subscriptionNumberOfOrdersBasedOnPeriodicity = $item->getVariant()->getProduct()->getProgram()->getMaximumSubscription();
                    } else {
                        $subscriptionNumberOfOrdersBasedOnPeriodicity = ($item->getVariant()->getProduct()->getProgram()->getMaximumSubscription() / 4);
                    }
                }
            }
        }
        if(count($subscription->getOrders()) >= $subscriptionNumberOfOrdersBasedOnPeriodicity && $subscriptionNumberOfOrdersBasedOnPeriodicity > 0) {
            return new JsonResponse(APIResponses::NUMBER_OR_ORDERS_FULLFILED, Response::HTTP_BAD_REQUEST);
        }
    }

    //Setting the primary order data
    public function setUpPrimaryOrderData(OrderInterface $latestOrder, OrderInterface &$newOrder) {
        $newOrder->setCustomer($latestOrder->getCustomer());
        $newOrder->setChannel($latestOrder->getChannel());
        $newOrder->setLocaleCode($latestOrder->getLocaleCode());
        $newOrder->setCurrencyCode($latestOrder->getCurrencyCode());
        $newOrder->setNotes($latestOrder->getNotes());
    }

    //Generating the new expected delivery date or the delivered at date
    public function generateNewExpectedDeliveryDate() {
        $expectedDeliveryDate = new \DateTime();
        $expectedDeliveryDate->add(new \DateInterval('P28D'));
        if ($expectedDeliveryDate->format('D') == "Sat") {
            $expectedDeliveryDate->add(new \DateInterval('P2D'));
        } elseif ($expectedDeliveryDate->format('D') == "Sun") {
            $expectedDeliveryDate->add(new \DateInterval('P1D'));
        }
        return $expectedDeliveryDate;
    }

    //Setting up the new order's shipping and billing addresses
    public function setUpNewOrderShippingAddresses(OrderInterface $latestOrder, OrderInterface &$newOrder) {
        /** @var Address $shippingAddress */
        $shippingAddress = $this->addressFactory->createNew();
        /** @var Address $billingAddress */
        $billingAddress = $this->addressFactory->createNew();

        $shippingAddress->setCustomer($latestOrder->getShippingAddress()->getCustomer());
        $shippingAddress->setCity($latestOrder->getShippingAddress()->getCity());
        $shippingAddress->setStreet($latestOrder->getShippingAddress()->getStreet());
        $shippingAddress->setCompany($latestOrder->getShippingAddress()->getCompany());
        $shippingAddress->setCountryCode($latestOrder->getShippingAddress()->getCountryCode());
        $shippingAddress->setProvinceCode($latestOrder->getShippingAddress()->getProvinceCode());
        $shippingAddress->setProvinceName($latestOrder->getShippingAddress()->getProvinceName());
        $shippingAddress->setPostcode($latestOrder->getShippingAddress()->getPostcode());
        $shippingAddress->setFirstName($latestOrder->getShippingAddress()->getFirstName());
        $shippingAddress->setLastName($latestOrder->getShippingAddress()->getLastName());
        $shippingAddress->setPhoneNumber($latestOrder->getShippingAddress()->getPhoneNumber());
        $this->addressManager->persist($shippingAddress);

        $newOrder->setShippingAddress($shippingAddress);

        $billingAddress->setCustomer($latestOrder->getBillingAddress()->getCustomer());
        $billingAddress->setCity($latestOrder->getBillingAddress()->getCity());
        $billingAddress->setStreet($latestOrder->getBillingAddress()->getStreet());
        $billingAddress->setCompany($latestOrder->getBillingAddress()->getCompany());
        $billingAddress->setCountryCode($latestOrder->getBillingAddress()->getCountryCode());
        $billingAddress->setProvinceCode($latestOrder->getBillingAddress()->getProvinceCode());
        $billingAddress->setProvinceName($latestOrder->getBillingAddress()->getProvinceName());
        $billingAddress->setPostcode($latestOrder->getBillingAddress()->getPostcode());
        $billingAddress->setFirstName($latestOrder->getBillingAddress()->getFirstName());
        $billingAddress->setLastName($latestOrder->getBillingAddress()->getLastName());
        $billingAddress->setPhoneNumber($latestOrder->getBillingAddress()->getPhoneNumber());
        $this->addressManager->persist($billingAddress);

        $newOrder->setBillingAddress($billingAddress);
    }

    //Generating order from subscription order based on the subscription id
    public function generateOrderFromSubscriptionOrder($subsciptionID) {
        /** @var SubscriptionOrder $subscriptionOrder */
        $subscriptionOrder = $this->subscriptionOrderRepository->findOneBy(['subscription' => $subsciptionID, 'isValidated' => 0]);
        if(empty($subscriptionOrder)) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_ORDER_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        /** @var OrderInterface $order */
        $order = $this->orderFactory->createNew();

        $this->setUpPrimaryOrderData($subscriptionOrder, $order);

        $order->setState(OrderStates::STATE_NEW);
        $order->setPaymentState(OrderStates::STATE_AWAITING_PAYMENT);
        $order->setCheckoutState(OrderCheckoutStates::STATE_COMPLETED);
        $order->setShippingState(OrderShippingStates::STATE_READY);
        $order->setCheckoutCompletedAt(new \DateTime());
        $order->setNumber($this->numberGenerator->generate($order));

        $this->setUpNewOrderShippingAddresses($subscriptionOrder, $order);
        $this->setUpNewOrderSelectedProducts($subscriptionOrder, $order);
        $this->setUpTheOrderPayment($subscriptionOrder, $order);
        $this->setUpTheOrderShippement($subscriptionOrder, $order);

        $order->setSubscription($subscriptionOrder->getSubscription());
        $promotionCoupon = $this->promotionCouponRepository->findOneBy(['code' => $subscriptionOrder->getCouponCode()]);
        if(!empty($promotionCoupon)) {
            $order->setPromotionCoupon($promotionCoupon);
        }
        $this->container->get('sylius.order_processing.order_processor')->process($order);

        //Setting the new changes in the database
        $this->orderManager->persist($order);
        $subscriptionOrder->getSubscription()->addOrder($order);
        $subscriptionOrder->setIsValidated(true);
        //$subscriptionOrder->setPaymentState(OrderStates::STATE_PAID);
        $this->subscriptionManager->persist($subscriptionOrder->getSubscription());
        $this->subscriptionOrderManager->persist($subscriptionOrder);

        $this->subscriptionOrderManager->flush();
        $this->addressManager->flush();
        $this->orderItemDetailsManager->flush();
        $this->orderItemManager->flush();
        $this->orderManager->flush();
        $this->subscriptionManager->flush();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add('success', [
            'message' => 'sylius.resource.create',
            'parameters' => ['%resource%' => "Order"],
        ]);

        return new JsonResponse(["status" => Response::HTTP_CREATED, "message" => APIResponses::ORDER_CREATED, "id" => $order->getId()], Response::HTTP_CREATED);
    }

    public function setUpTheOrderPayment(SubscriptionOrder $subscriptionOrder, OrderInterface &$order) {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();
        $payment->setMethod($subscriptionOrder->getOrder()->getPayments()[0]->getMethod());
        $payment->setAmount($subscriptionOrder->getOrder()->getTotal());
        $payment->setState(OrderStates::STATE_NEW);
        $payment->setCurrencyCode($order->getCurrencyCode());

        $payment->setOrder($order);
        $order->addPayment($payment);
        $this->paymentManager->persist($payment);
    }

    public function setUpTheOrderShippement(SubscriptionOrder $subscriptionOrder, OrderInterface &$order) {
        /** @var Shipment $shipment */
        $shipment = $this->shipmentFactory->createNew();
        $shipment->setMethod($subscriptionOrder->getOrder()->getShipments()[0]->getMethod());
        //$shipment->setTracking($subscriptionOrder->getOrder()->getShipments()[0]->getTracking());
        $shipment->setState(OrderShippingStates::STATE_READY);
        $shipment->setShippedAt($subscriptionOrder->getExpectedDeliveryDate());

        $shipment->setOrder($order);
        $order->addShipment($shipment);
        $this->shipmentManager->persist($shipment);
    }

    //Setting the order's selected products
    public function setUpNewOrderSelectedProducts(OrderInterface $latestOrder, OrderInterface &$newOrder, $isSubscriptionOrder = false) {
        $orderItemFactory = $this->orderItemFactory;
        $orderItemManager = $this->orderItemManager;
        $orderItemDetailsFactory = $this->orderItemDetailsFactory;
        $orderItemDetailsManager = $this->orderItemDetailsManager;
        if($isSubscriptionOrder) {
            $orderItemFactory = $this->subscriptionOrderItemFactory;
            $orderItemManager = $this->subscriptionOrderItemManager;
            $orderItemDetailsFactory = $this->subscriptionOrderItemDetailsFactory;
            $orderItemDetailsManager = $this->subscriptionOrderItemDetailsManager;
        }
        foreach ($latestOrder->getItems() as $item) {
            if(!empty($item->getVariant()->getProduct()->getProgram())) {
                $orderItemProgram = $orderItemFactory->createNew();
                $orderItemProgram->setVariant($item->getVariant());
                if(!$isSubscriptionOrder) {
                    $this->orderItemQuantityModifier->modify($orderItemProgram, 1);
                } else {
                    $orderItemProgram->setQuantity(1);
                }
                $price = $item->getVariant()->getChannelPricingForChannel($this->container->get('sylius.context.channel')->getChannel())->getPrice();
                $orderItemProgram->setUnitPrice($price);

                foreach ($item->getOrderItemDetails() as $orderItemDetail) {
                    $new0rderItemDetail = $orderItemDetailsFactory->createNew();
                    $new0rderItemDetail->setVariant($orderItemDetail->getVariant());
                    $new0rderItemDetail->setQuantity($orderItemDetail->getQuantity());
                    $new0rderItemDetail->setOrderItem($orderItemProgram);
                    $new0rderItemDetail->setCreatedAt(new \DateTime());
                    $new0rderItemDetail->setTaxon($orderItemDetail->getTaxon());

                    $orderItemProgram->addOrderItemDetail($new0rderItemDetail);
                    $orderItemDetailsManager->persist($new0rderItemDetail);
                }
                $orderItemManager->persist($orderItemProgram);
                $newOrder->addItem($orderItemProgram);
            } elseif (($isSubscriptionOrder && $item->isOption()) || (!$isSubscriptionOrder)) {
                $optionOrderItem = $orderItemFactory->createNew();
                $optionOrderItem->setVariant($item->getVariant());
                if(!$isSubscriptionOrder) {
                    $this->orderItemQuantityModifier->modify($optionOrderItem, $item->getQuantity());

                } else {
                    $optionOrderItem->setQuantity($item->getQuantity());
                }
                $price = $item->getVariant()->getChannelPricingForChannel($this->container->get('sylius.context.channel')->getChannel())->getPrice();
                $optionOrderItem->setUnitPrice($price);
                $optionOrderItem->setIsOption(true);
                $optionOrderItem->setTaxon($item->getTaxon());
                $orderItemManager->persist($optionOrderItem);
                $newOrder->addItem($optionOrderItem);
            }
        }
    }

}
