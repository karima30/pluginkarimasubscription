<?php

namespace Ksante\SubscriptionPlugin\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Ksante\SubscriptionPlugin\Controller\ApiResponses;
use Ksante\SubscriptionPlugin\Entity\Subscription;
use Ksante\SubscriptionPlugin\Entity\SubscriptionLog;
use Ksante\SubscriptionPlugin\State\OrderStates;
use Ksante\SubscriptionPlugin\State\SubscriptionStates;
use \DateTime;
use Sylius\Bundle\OrderBundle\NumberGenerator\OrderNumberGeneratorInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderShippingStates;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sylius\Component\Core\Model\Payment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SubscriptionService extends ResourceController
{
    /** @var ContainerInterface */
    protected $container;
    protected $currentAdmin;
    protected $orderService;
    protected $emailingService;
    protected $orderClassName;

    /** @var SessionInterface */
    protected $session;
    protected $orderItemQuantityModifier;

    /** @var OrderNumberGeneratorInterface */
    protected $numberGenerator;

    protected $productFactory;
    protected $productTranslationFactory;
    protected $productVariantFactory;
    protected $channelPricingFactory;
    protected $orderFactory;
    protected $paymentFactory;
    protected $subscriptionLogFactory;
    protected $orderItemFactory;

    protected $subscriptionRepository;
    protected $customerRepository;

    protected $subscriptionManager;
    protected $productManager;
    protected $productTranslationManager;
    protected $productVariantManager;
    protected $channelPricingManager;
    protected $orderManager;
    protected $paymentManager;
    protected $subscriptionLogManager;
    protected $subscriptionOrderManager;
    protected $orderItemManager;

    public function __construct(ContainerInterface $container, OrderService $orderService, EmailingService $emailingService, $orderClassName, OrderNumberGeneratorInterface $numberGenerator, SessionInterface $session) {
        $this->container = $container;
        //Getting the required services including the Repositories, Managers, and Factories
        if(!empty($this->container->get('security.token_storage')->getToken())) {
            $this->currentAdmin = $this->container->get('security.token_storage')->getToken()->getUser();
        }
        $this->orderService = $orderService;
        $this->emailingService = $emailingService;

        $this->subscriptionRepository = $this->container->get('ksante_subscription.repository.subscription');
        $this->customerRepository = $this->container->get('sylius.repository.customer');

        $this->productFactory = $this->container->get('sylius.factory.product');
        $this->productTranslationFactory = $this->container->get('sylius.factory.product_translation');
        $this->productVariantFactory = $this->container->get('sylius.factory.product_variant');
        $this->channelPricingFactory = $this->container->get('sylius.factory.channel_pricing');
        $this->orderFactory = $this->container->get('sylius.factory.order');
        $this->paymentFactory = $this->container->get('sylius.factory.payment');
        $this->subscriptionLogFactory = $this->container->get('ksante_subscription.factory.subscription_logs');
        $this->orderItemFactory = $this->container->get('sylius.factory.order_item');

        $this->productManager = $this->container->get('sylius.manager.product');
        $this->productTranslationManager = $this->container->get('sylius.manager.product_translation');
        $this->productVariantManager = $this->container->get('sylius.manager.product_variant');
        $this->channelPricingManager = $this->container->get('sylius.manager.channel_pricing');
        $this->orderManager = $this->container->get('sylius.manager.order');
        $this->paymentManager = $this->container->get('sylius.manager.payment');
        $this->subscriptionManager = $this->container->get('ksante_subscription.manager.subscription');
        $this->subscriptionLogManager = $this->container->get('ksante_subscription.manager.subscription_logs');
        $this->subscriptionOrderManager = $this->container->get('ksante_subscription.manager.subscription_order');
        $this->orderItemManager = $this->container->get('sylius.manager.order_item');

        $this->session = $session;
        $this->orderClassName = $orderClassName;
        $this->numberGenerator = $numberGenerator;
        $this->orderItemQuantityModifier = $this->container->get('sylius.order_item_quantity_modifier');
    }

    //Setting the new subscription status
    public function settingNewSubscriptionStatus(Subscription &$subscription, $newState, $newSubscriptionOrderState, DateTime $newDeliveryDate, $ifUser = false) {
        /** @var SubscriptionLog $subscriptionLog */
        $subscriptionLog = $this->subscriptionLogFactory->createNew();

        if($ifUser) {
            $subscriptionLog->setCustomer($subscription->getCustomer());
        } else {
            $subscriptionLog->setAgent($this->currentAdmin);
        }

        $subscriptionLog->setCreatedAt(new \DateTime());
        $subscriptionLog->setPreviousStatus($subscription->getState());
        $subscriptionLog->setNewStatus($newState);
        $subscriptionLog->setSubscription($subscription);
        $subscription->setState($newState);
        $subscription->addSubscriptionLog($subscriptionLog);

        if(!$subscription->getSubscriptionOrders()->isEmpty()) {
            //$subscription->getLatestSubscriptionOrder()->setPaymentState($newSubscriptionOrderState);
            $subscription->getLatestSubscriptionOrder()->setExpectedDeliveryDate($newDeliveryDate);
            $this->subscriptionOrderManager->persist($subscription->getLatestSubscriptionOrder());
        }

        $this->subscriptionLogManager->persist($subscriptionLog);
        $this->subscriptionManager->persist($subscription);

        $this->subscriptionOrderManager->flush();
        $this->subscriptionLogManager->flush();
        $this->subscriptionManager->flush();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add('success', [
            'message' => 'ksante_subscription.flashes.updatedSubscriptionStatus',
            'parameters' => [],
        ]);
    }

    //Pausing a subscription
    public function pauseSubscription(int $id)
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $id]);
        if(empty($subscription)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.subscriptionDoesNotExist');
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }
        if($subscription->getState() != SubscriptionStates::ON_GOING) {
            return new JsonResponse('Pause Subscription: '.$id);
        }
        $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::PAUSED, OrderStates::PAUSED, DateTime::createFromFormat('d/m/Y', '01/01/2000'));

        $mailSendingFeedback = $this->emailingService->sendSubscriptionEmailToCustomer('emails/Subscription/'.$this->get('sylius.context.locale')->getLocaleCode().'_pauseSubscription.html.twig', $subscription->getCustomer()->getEmail(), $this->get('translator')->trans('ksante_subscription.ui.pause_subscription_subject'), ['customerName' => $subscription->getCustomer()->getFirstName().' '.$subscription->getCustomer()->getLastName(), 'webSitePath' => $_SERVER['FRONT_END_URL'], 'customerServiceEmailAddress' => $_SERVER['DIETBON_EMAIL']]);
        if($mailSendingFeedback != Response::HTTP_ACCEPTED) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $this->session->getBag('flashes');
            $flashBag->add('error', [
                'message' => 'ksante_subscription.flashes.errorEmailing',
                'parameters' => [],
            ]);
            return new JsonResponse(ApiResponses::ERROR_EMAILING, Response::HTTP_FORBIDDEN);
        }
        return new JsonResponse('Pause Subscription: '.$id, Response::HTTP_ACCEPTED);
    }

    //Auto pausing a subscription
    public function autoPauseSubscription(int $id)
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $id]);
        if(empty($subscription)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.subscriptionDoesNotExist');
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        if($subscription->getState() != SubscriptionStates::ON_GOING) {
            return new JsonResponse('Auto pause Subscription: '.$id);
        }

        $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::AUTO_PAUSED, OrderStates::PAUSED, DateTime::createFromFormat('d/m/Y', '01/01/2000'));

        return new JsonResponse('Auto pause Subscription: '.$id);
    }

    //Unsubscribe
    public function unsubscribe(int $id)
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $id]);
        if(empty($subscription)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.subscriptionDoesNotExist');
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        if($subscription->getState() == SubscriptionStates::STOPPED) {
            return new JsonResponse('Unsubscribe: '.$id);
        }

        $checkingTheMinSubscriptionWithTheCurrentPayedOrders = $this->checkingMinSubscriptionWithCurrentPayedOrders($subscription);

        $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::STOPPED, OrderStates::CANCELLED, DateTime::createFromFormat('d/m/Y', '01/01/2000'));

        $mailSendingFeedback = $this->emailingService->sendSubscriptionEmailToCustomer('emails/Subscription/'.$this->get('sylius.context.locale')->getLocaleCode().'_unsubscribe.html.twig', $subscription->getCustomer()->getEmail(), $this->get('translator')->trans('ksante_subscription.ui.unsubscribe_subject'), ['customerName' => $subscription->getCustomer()->getFirstName().' '.$subscription->getCustomer()->getLastName(), 'customerServiceEmailAddress' => $_SERVER['DIETBON_EMAIL']]);
        if($mailSendingFeedback != Response::HTTP_ACCEPTED) {
            /** @var FlashBagInterface $flashBag */
            $flashBag = $this->session->getBag('flashes');
            $flashBag->add('error', [
                'message' => 'ksante_subscription.flashes.errorEmailing',
                'parameters' => [],
            ]);
            return new JsonResponse(ApiResponses::ERROR_EMAILING, Response::HTTP_FORBIDDEN);
        }
        return new JsonResponse('Unsubscribe: '.$id);
    }

    //Resend payment email
    public function resendPaymentEmail(int $id)
    {
        return new JsonResponse('Resend Payment Email: '.$id);
    }

    //Access the paybox page
    public function accessPayboxPage(int $id)
    {
        return new JsonResponse('Access Paybox Page: '.$id);
    }

    //Resume subscription
    public function resumeSubscription(int $id)
    {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $id]);
        if(empty($subscription)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.subscriptionDoesNotExist');
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        if($subscription->getState() != SubscriptionStates::PAUSED) {
            return new JsonResponse('Resumption Subscription: '.$id);
        }

        $this->orderService->setUpNewSubscriptionOrder($subscription);

        $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::ON_GOING, OrderStates::STATE_AWAITING_PAYMENT, $this->container->get('ksante_subscription_plugin.service.order_service')->generateNewExpectedDeliveryDate());

        return new JsonResponse('Resumption Subscription: '.$id);
    }

    //Updating the subscription state
    public function updateSubscriptionState(Payment $payment) {
        if(!empty($payment->getOrder()->getSubscription()) || !$payment->getOrder()->isRegularizationOrder()) {
            /** @var Subscription $subscription */
            $subscription = $payment->getOrder()->getSubscription();
            if($subscription->getState() == SubscriptionStates::AUTO_PAUSED) {
                $subscription->setState(SubscriptionStates::PAUSED);
                $this->subscriptionManager->persist($subscription);
                $mailSendingFeedback = $this->emailingService->sendSubscriptionEmailToCustomer('emails/Subscription/'.$this->get('sylius.context.locale')->getLocaleCode().'_pauseSubscription.html.twig', $subscription->getCustomer()->getEmail(), $this->get('translator')->trans('ksante_subscription.ui.pause_subscription_subject'), ['customerName' => $subscription->getCustomer()->getFirstName().' '.$subscription->getCustomer()->getLastName(), 'webSitePath' => $_SERVER['FRONT_END_URL'], 'customerServiceEmailAddress' => $_SERVER['DIETBON_EMAIL']]);
                if($mailSendingFeedback != Response::HTTP_ACCEPTED) {
                    /** @var FlashBagInterface $flashBag */
                    $flashBag = $this->session->getBag('flashes');
                    $flashBag->add('error', [
                        'message' => 'ksante_subscription.flashes.errorEmailing',
                        'parameters' => [],
                    ]);
                }

            } else {
                $checkingTheSubscriptionPeriodicity = $this->orderService->checkSubscriptionPeriodicity($subscription);
                if($checkingTheSubscriptionPeriodicity instanceof JsonResponse) {
                    $subscription->setState(SubscriptionStates::FINALIZED);
                    $this->subscriptionManager->persist($subscription);
                }
            }
            $this->subscriptionManager->flush();
        }
    }

    //Checking the minimum periodicity before updating the subscription status based on the number of payed orders
    public function checkingMinSubscriptionWithCurrentPayedOrders(Subscription $subscription) {
        /** @var OrderInterface $latestOrder */
        $latestOrder = $subscription->getLatestOrder();
        foreach ($latestOrder->getItems() as $item) {
            if(!empty($item->getVariant()->getProduct()->getProgram())) {
                if($item->getVariant()->getProduct()->getProgram()->getMinimumSubscription() > $subscription->countOrders()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function updateNextOrderDate($parameters) {
        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $parameters['id']]);
        if(empty($subscription)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.subscriptionDoesNotExist');
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        $subscriptionOrder = $subscription->getLatestSubscriptionOrder();
        if(!empty($subscriptionOrder)) {
            $subscriptionOrder->setExpectedDeliveryDate(DateTime::createFromFormat('Y-m-d', $parameters['nextOrderDate']));
            $this->subscriptionOrderManager->persist($subscriptionOrder);
        }

        $this->subscriptionOrderManager->flush();

        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add('success', [
            'message' => 'ksante_subscription.flashes.updatedNextOrderDate',
            'parameters' => [],
        ]);
    }

    public function sendSubscriptionDoesNotExistFlashMessage($message) {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add('error', [
            'message' => $message,
            'parameters' => [],
        ]);
    }

    public function csvExport() {
        $subscriptions = $this->subscriptionRepository->getPayedSubscriptions($this->orderClassName)->getQuery()->getResult();
        if(empty($subscriptions)) {
            $this->sendSubscriptionDoesNotExistFlashMessage('ksante_subscription.flashes.emptySubscriptionsList');
        }

        $subscriptionRows = array();
        array_push($subscriptionRows, (
            $this->get('translator')->trans('ksante_subscription.ui.program').';'.
            $this->get('translator')->trans('sylius.ui.first_name').';'.
            $this->get('translator')->trans('sylius.ui.last_name').';'.
            $this->get('translator')->trans('sylius.ui.email').';'.
            $this->get('translator')->trans('sylius.ui.channel').';'.
            $this->get('translator')->trans('sylius.ui.status').';'.
            $this->get('translator')->trans('ksante_subscription.ui.completedOrdersCount').';'.
            $this->get('translator')->trans('ksante_subscription.ui.shippedOrdersCount').';'.
            $this->get('translator')->trans('ksante_subscription.ui.firstOrderDate').';'.
            $this->get('translator')->trans('ksante_subscription.ui.lastOrderDate').';'.
            $this->get('translator')->trans('ksante_subscription.ui.nextOrderDate'))
        );

        foreach ($subscriptions as $subscription) {
            $data = array(
                $subscription->getProgram()->getProduct()->getCode(),
                $subscription->getCustomer()->getFirstName(),
                $subscription->getCustomer()->getLastName(),
                $subscription->getCustomer()->getEmail(),
                $subscription->getChannel()->getName(),
                $this->get('translator')->trans('ksante_subscription.ui.'. $subscription->getState()),
                $subscription->getCompletedOrdersCount(),
                $subscription->getShippedOrdersCount(),
                $subscription->getFirstOrderDate()->format('Y-m-d'),
                $subscription->getLastOrderDate()->format('Y-m-d'),
                (empty($subscription->getNextOrderDate()) ? "" : $subscription->getNextOrderDate()->format('Y-m-d'))
            );

            $subscriptionRows[] = implode(';', $data);
        }

        return implode("\n", $subscriptionRows);
    }

    public function pauseSubscriptionByUser($parameters) {
        $checkingUserParameters = $this->checkUpdateSubscriptionFromUser($parameters);
        if($checkingUserParameters instanceof JsonResponse) {
            return $checkingUserParameters;
        }
        $subscription = $checkingUserParameters;

        if($subscription->getState() != SubscriptionStates::PAUSED) {
            $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::PAUSED, OrderStates::PAUSED, DateTime::createFromFormat('d/m/Y', '01/01/2000'), true);

            $mailSendingFeedback = $this->emailingService->sendSubscriptionEmailToCustomer('emails/Subscription/'.$this->get('sylius.context.locale')->getLocaleCode().'_pauseSubscription.html.twig', $subscription->getCustomer()->getEmail(), $this->get('translator')->trans('ksante_subscription.ui.pause_subscription_subject'), ['customerName' => $subscription->getCustomer()->getFirstName().' '.$subscription->getCustomer()->getLastName(), 'webSitePath' => $_SERVER['FRONT_END_URL'], 'customerServiceEmailAddress' => $_SERVER['DIETBON_EMAIL']]);

            if($mailSendingFeedback != Response::HTTP_ACCEPTED) {
                return new JsonResponse(ApiResponses::ERROR_EMAILING, Response::HTTP_FORBIDDEN);
            }
        }

        return new JsonResponse(["status" => Response::HTTP_ACCEPTED, "message" => APIResponses::SUBSCRIPTION_STATUS_UPDATED, "id" => $subscription->getId()], Response::HTTP_CREATED);
    }

    public function autoPauseSubscriptionByUser($parameters) {
        $checkingUserParameters = $this->checkUpdateSubscriptionFromUser($parameters);
        if($checkingUserParameters instanceof JsonResponse) {
            return $checkingUserParameters;
        }
        $subscription = $checkingUserParameters;
        if($subscription->getState() != SubscriptionStates::AUTO_PAUSED) {
            $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::AUTO_PAUSED, OrderStates::PAUSED, DateTime::createFromFormat('d/m/Y', '01/01/2000'), true);
        }
        return new JsonResponse(["status" => Response::HTTP_ACCEPTED, "message" => APIResponses::SUBSCRIPTION_STATUS_UPDATED, "id" => $subscription->getId()], Response::HTTP_CREATED);
    }

    public function resumeSubscriptionByUser ($parameters) {
        $checkingUserParameters = $this->checkUpdateSubscriptionFromUser($parameters);
        if($checkingUserParameters instanceof JsonResponse) {
            return $checkingUserParameters;
        }
        $subscription = $checkingUserParameters;
        if($subscription->getState() == SubscriptionStates::STOPPED) {
            return new JsonResponse(APIResponses::CANT_RESUME_STOPPED_SUBSCRIPTION, Response::HTTP_BAD_REQUEST);
        }
        if($subscription->getState() != SubscriptionStates::ON_GOING) {
            $this->settingNewSubscriptionStatus($subscription, SubscriptionStates::ON_GOING, OrderStates::STATE_AWAITING_PAYMENT, $this->container->get('ksante_subscription_plugin.service.order_service')->generateNewExpectedDeliveryDate(), true);
        }
        return new JsonResponse(["status" => Response::HTTP_ACCEPTED, "message" => APIResponses::SUBSCRIPTION_STATUS_UPDATED, "id" => $subscription->getId()], Response::HTTP_CREATED);
    }

    public function checkUpdateSubscriptionFromUser($parameters) {
        if (!array_key_exists('subscription', $parameters)) {
            return new JsonResponse(APIResponses::UNPROVIDED_SUBSCRIPTION, Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('customer', $parameters)) {
            return new JsonResponse(APIResponses::UNPROVIDED_CUSTOMER, Response::HTTP_BAD_REQUEST);
        }

        /** @var Subscription $subscription */
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $parameters['subscription']]);
        if(empty($subscription)) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        $customer = $this->customerRepository->findOneBy(['id' => $parameters['customer']]);
        if(empty($customer)) {
            return new JsonResponse(APIResponses::CUSTOMER_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }
        if($subscription->getCustomer()->getId() != $customer->getId()) {
            return new JsonResponse(APIResponses::SUBSCRIPTION_DOES_NOT_BELONG_TO_CUSTOMER, Response::HTTP_BAD_REQUEST);
        }

        return $subscription;
    }

    public function getSubscriptionsByCustomerID(int $id) {
        $serializedSubscriptionsList = [];
        $customer = $this->customerRepository->findOneBy(['id' => $id]);
        if(empty($customer)) {
            return new JsonResponse(APIResponses::CUSTOMER_NOT_EXIST, Response::HTTP_BAD_REQUEST);
        }

        $subscriptions = $this->subscriptionRepository->getPayedSubscriptionsByCustomer($customer->getId(), $this->orderClassName)->getQuery()->getResult();

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer(array($normalizer));

        foreach ($subscriptions as $subscription) {
            $serializedSubscriptionsList [] = $serializer->normalize($subscription, 'json', ['groups' => ['subscription']]);
        }

        return new JsonResponse($serializedSubscriptionsList, Response::HTTP_ACCEPTED);
    }

    public function generateRegularizationOrder(Subscription $subscription, int $amount, OrderInterface $order = null) {
        /** @var ProductInterface $product */
        $product = $this->createNewRegularizationProduct($subscription);
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantFactory->createNew();
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingFactory->createNew();
        /** @var ProductTranslationInterface $productTranslation */
        $productTranslation = $this->productTranslationFactory->createNew();
        /** @var OrderInterface $order */
        $order = $this->orderFactory->createNew();

        $this->setRegularizationProductVariantPriceAndTranslation($product, $productVariant, $channelPricing, $amount, $productTranslation);

        $this->createTheRegularizationOrder($order, $product, $subscription);

        $this->productTranslationManager->flush();
        $this->channelPricingManager->flush();
        $this->productVariantManager->flush();
        $this->productManager->flush();
        $this->orderItemManager->flush();
        $this->paymentManager->flush();
        $this->orderManager->flush();
        $this->subscriptionManager->flush();

        return $order;
    }

    public function createNewRegularizationProduct(Subscription $subscription) {
        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();
        $product->setCode($this->get('translator')->trans('ksante_subscription.ui.regularization').'_'.$subscription->getId().'_'.$subscription->countRegularizationOrders());
        $product->setIsRegularizationProduct(true);
        $product->addChannel($subscription->getChannel());
        $product->setEnabled(true);
        $this->productManager->persist($product);

        return $product;
    }

    public function createTheRegularizationOrder(OrderInterface &$order, ProductInterface $product, Subscription &$subscription) {
        $this->orderService->setUpPrimaryOrderData($subscription->getLatestOrder(), $order);

        $order->setState(OrderStates::STATE_NEW);
        $order->setPaymentState(OrderStates::STATE_AWAITING_PAYMENT);
        $order->setCheckoutState(OrderCheckoutStates::STATE_COMPLETED);
        $order->setShippingState(OrderShippingStates::STATE_READY);
        $order->setCheckoutCompletedAt(new \DateTime());
        $order->setNumber($this->numberGenerator->generate($order));

        $this->setUpRegulaizationProductToOrder($order, $product);
        //$this->container->get('sylius.order_processing.order_processor')->process($order);

        $this->setUpRegularizationOrderPayment($order, $subscription->getLatestOrder());

        $this->orderManager->persist($order);

        $subscription->addOrder($order);
        $this->subscriptionManager->persist($subscription);

    }

    public function setUpRegularizationOrderPayment(OrderInterface &$order, OrderInterface $latestOrder) {
        /** @var PaymentInterface $payment */
        $payment = $this->paymentFactory->createNew();

        $payment->setOrder($order);
        $payment->setCurrencyCode($latestOrder->getCurrencyCode());
        $payment->setState(OrderStates::STATE_AWAITING_PAYMENT);
        $payment->setMethod($latestOrder->getPayments()[0]->getMethod());
        $payment->setAmount($order->getTotal());
        $payment->setState(OrderStates::STATE_NEW);
        $this->paymentManager->persist($payment);

        $order->addPayment($payment);
    }

    public function setUpRegulaizationProductToOrder(OrderInterface &$order, ProductInterface &$product) {
        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($product->getVariants()[0]);
        $this->orderItemQuantityModifier->modify($orderItem, 1);
        $orderItem->setUnitPrice($product->getVariants()[0]->getChannelPricingForChannel($order->getChannel())->getPrice());
        $orderItem->setOrder($order);

        $this->orderItemManager->persist($orderItem);
    }

    public function setRegularizationProductVariantPriceAndTranslation(ProductInterface &$product, ProductVariantInterface &$productVariant, ChannelPricingInterface &$channelPricing, int $amount, ProductTranslationInterface &$productTranslation) {
        $productVariant->setCode($product->getCode());
        $productVariant->setProduct($product);

        $channelPricing->setPrice($amount);
        $channelPricing->setProductVariant($productVariant);
        $channelPricing->setChannelCode($product->getChannels()[0]->getCode());
        $productVariant->addChannelPricing($channelPricing);

        $productTranslation->setName($product->getCode());
        $productTranslation->setSlug($product->getCode());
        $productTranslation->setLocale($this->get('sylius.context.locale')->getLocaleCode());
        $product->addTranslation($productTranslation);

        $this->productTranslationManager->persist($productTranslation);
        $this->channelPricingManager->persist($channelPricing);
        $this->productVariantManager->persist($productVariant);

        $product->addVariant($productVariant);
    }

}
