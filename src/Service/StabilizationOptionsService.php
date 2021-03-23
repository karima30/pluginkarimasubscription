<?php

namespace Ksante\SubscriptionPlugin\Service;

use Ksante\SubscriptionPlugin\Entity\StabilizationCategory;
use Ksante\SubscriptionPlugin\Entity\StabilizationNumberOfDaysPerWeek;
use Ksante\SubscriptionPlugin\Entity\StabilizationNumberOfWeeksInterval;
use Ksante\SubscriptionPlugin\Entity\StabilizationOptions;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;

class StabilizationOptionsService
{
    /** @var ContainerInterface */
    protected $container;

    protected $stabilizationOptionsFactory;

    protected $customerRepository;
    protected $stabilizationOptionsRepository;
    protected $channelRepository;
    protected $stabilizationCategoryRepository;
    protected $stabilizationNumberOfWeeksIntervalRepository;
    protected $stabilizationNumberOfDaysPerWeekRepository;
    protected $subscriptionRepository;

    protected $stabilizationOptionsManager;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        //Getting the required services including the Repositories, Managers, and Factories
        $this->stabilizationOptionsFactory = $this->container->get('ksante_subscription.factory.stabilization_options');

        $this->customerRepository = $this->container->get('sylius.repository.customer');
        $this->stabilizationOptionsRepository = $this->container->get('ksante_subscription.repository.stabilization_options');
        $this->stabilizationNumberOfDaysPerWeekRepository = $this->container->get('ksante_subscription.repository.stabilization_number_of_days_per_week');
        $this->stabilizationNumberOfWeeksIntervalRepository = $this->container->get('ksante_subscription.repository.stabilization_number_of_weeks_interval');
        $this->stabilizationCategoryRepository = $this->container->get('ksante_subscription.repository.stabilization_category');
        $this->channelRepository = $this->container->get('sylius.repository.channel');
        $this->subscriptionRepository = $this->container->get('ksante_subscription.repository.subscription');

        $this->stabilizationOptionsManager = $this->container->get('ksante_subscription.manager.stabilization_options');
    }

    //Updating the stabilization option price
    public function updateStabilizationOptionPrice($parameters)
    {
        /** @var StabilizationOptions $stabilizationOption */
        $stabilizationOption = $this->stabilizationOptionsRepository->findOneBy(['id' => $parameters['id']]);
        $stabilizationOption->setPrice($parameters['price']);

        $this->stabilizationOptionsManager->persist($stabilizationOption);
        $this->stabilizationOptionsManager->flush();

        return new JsonResponse();
    }

    //Generating the stabilization options based on the channles, currencies, number of days per week, and the stabilization categories
    public function generateStabilizationOptions()
    {
        $stabilizationNumberOfWeeksIntervalElements = $this->stabilizationNumberOfWeeksIntervalRepository->findAll();
        $stabilizationNumberOfDaysPerWeekElements = $this->stabilizationNumberOfDaysPerWeekRepository->findAll();
        $stabilizationCategoriesElements = $this->stabilizationCategoryRepository->findAll();
        $channels = $this->channelRepository->findAll();

        foreach ($channels as $channel) {
            foreach ($channel->getCurrencies() as $currency) {
                foreach ($stabilizationNumberOfWeeksIntervalElements as $stabilizationNumberOfWeeksIntervalElement) {
                    foreach ($stabilizationNumberOfDaysPerWeekElements as $stabilizationNumberOfDaysPerWeekElement) {
                        foreach ($stabilizationCategoriesElements as $stabilizationCategoryElement) {
                            $ifStabilizationOptionIsFound = !empty($this->stabilizationOptionsRepository->findOneBy(['channel' => $channel->getId(), 'currencyCode' => $currency->getCode(), 'stabilizationNumberOfDaysPerWeek' => $stabilizationNumberOfDaysPerWeekElement->getId(), 'stabilizationCategory' => $stabilizationCategoryElement->getId(), 'stabilizationNumberOfWeeksInterval' => $stabilizationNumberOfWeeksIntervalElement]));
                            if(!$ifStabilizationOptionIsFound) {
                                $this->createTheStabilizationOption($channel, $currency, $stabilizationNumberOfWeeksIntervalElement, $stabilizationNumberOfDaysPerWeekElement, $stabilizationCategoryElement);
                            }
                        }
                    }
                }
            }
        }
        $this->stabilizationOptionsManager->flush();
        return new JsonResponse();
    }

    //Creating a stabilization option containing the channel, the currency, the number of days per week, and the stabilization category
    public function createTheStabilizationOption(ChannelInterface $channel, CurrencyInterface $currency, StabilizationNumberOfWeeksInterval $stabilizationNumberOfWeeksIntervalElement, StabilizationNumberOfDaysPerWeek $stabilizationNumberOfDaysPerWeek, StabilizationCategory $stabilizationCategory) {
        /** @var StabilizationOptions $stabilizationOption */
        $stabilizationOption = $this->stabilizationOptionsFactory->createNew();

        $stabilizationOption->setChannel($channel);
        $stabilizationOption->setCurrencyCode($currency->getCode());
        $stabilizationOption->setStabilizationNumberOfWeeksInterval($stabilizationNumberOfWeeksIntervalElement);
        $stabilizationOption->setStabilizationNumberOfDaysPerWeek($stabilizationNumberOfDaysPerWeek);
        $stabilizationOption->setStabilizationCategory($stabilizationCategory);

        $this->stabilizationOptionsManager->persist($stabilizationOption);
    }

    //The get stabilization options based on the customer's number of weeks engagement
    public function getStabilizationOptionsByCustomer($id) {
        $serializedStabilizationOptionsList = [];

        if (!$this->container->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            //throw new AccessDeniedException('You have to be registered user to access this section.');
        }

        //$user = $this->container->get('security.token_storage')->getToken()->getUser();
        $user = $this->customerRepository->findOneBy(['id' => $id]);
        $channel = $this->container->get('sylius.context.channel')->getChannel();
        $currencyCode = $this->container->get('sylius.context.currency')->getCurrencyCode();

        $stabilizationOptions = $this->getStabilizationOptionsByCustomerChannelCurrency($user, $channel, $currencyCode);

        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizer = new ObjectNormalizer($classMetadataFactory);
        $serializer = new Serializer(array($normalizer));

        foreach ($stabilizationOptions as $stabilizationOption) {
            $serializedStabilizationOptionsList [] = $serializer->normalize($stabilizationOption, 'json', ['groups' => ['stabilization_option','stabilization_number_of_days_per_week_interval', 'stabilization_number_of_days_per_week', 'stabilization_category']]);
        }

        return new JsonResponse($serializedStabilizationOptionsList, Response::HTTP_ACCEPTED);
    }

    //Getting the matching stabilization options to the customer's engagement, channel, and currency
    public function getStabilizationOptionsByCustomerChannelCurrency(CustomerInterface $customer, ChannelInterface $channel, $currencyCode) {
        $customerNumberOfWeeks = $this->getCustomerNumberOfWeeksEngagement($customer);
        $stabilizationOptions = $this->stabilizationOptionsRepository->getStabilizationOptionsByNumberOfWeeksChannelIdCurrencyCode($customerNumberOfWeeks, $channel->getId(), $currencyCode);

        return $stabilizationOptions;
    }

    public function getCustomerNumberOfWeeksEngagement(CustomerInterface $customer) {
        $numberOfWeeksEngagement = 0;
        $customerSubscriptions = $this->subscriptionRepository->getSubscriptionsByCustomerID($customer->getId());

        foreach ($customerSubscriptions as $customerSubscription) {
            $numberOfWeeksEngagement += ($customerSubscription->countOrders() * 4);
        }

        return $numberOfWeeksEngagement;
    }

}
