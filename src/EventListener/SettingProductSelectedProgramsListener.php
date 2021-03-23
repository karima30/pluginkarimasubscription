<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\EventListener;

use Ksante\SubscriptionPlugin\Entity\Program;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SettingProductSelectedProgramsListener
{
    /** @var ContainerInterface */
    protected $container;

    protected $programRepository;
    protected $programManager;
    protected $programDetailsManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->programDetailsRepository = $container->get('ksante_subscription.repository.program_details');
        $this->programDetailsManager = $container->get('ksante_subscription.manager.program_details');
        $this->programManager = $container->get('ksante_subscription_plugin.manager.program');
    }

    public function settingProductSelectedPrograms(GenericEvent $event): void
    {
        $product = $event->getSubject();

        $this->setSelectedProgramDetailsToProduct($product);

        $this->removeUnselectedProgramsFromTheProductDetails($product);

        $this->programDetailsManager->flush();
        $this->programManager->flush();
    }

    public function setSelectedProgramDetailsToProduct(ProductInterface &$product) {
        foreach ($product->getProductSelectedPrograms()->getPrograms() as $programElement) {
            $program = $programElement->getProgram();
            $foundProductInProgramDetailsList = false;
            foreach ($programElement->getProgram()->getProgramDetails() as $programDetail) {
                if($programDetail->getProduct()->getId() == $product->getId()) {
                    $foundProductInProgramDetailsList = true;
                }
            }
            if(!$foundProductInProgramDetailsList) {
                $this->addProductToProgramDetailsList($product, $program);
            }
        }
    }

    public function addProductToProgramDetailsList(ProductInterface &$product, Program &$program) {
        $programDetails = $this->container->get('ksante_subscription.factory.program_details')->createNew();
        $programDetails->setProduct($product);
        $program->addProgramDetail($programDetails);

        $this->programDetailsManager->persist($programDetails);
        $this->programManager->persist($program);
    }

    public function removeUnselectedProgramsFromTheProductDetails(ProductInterface &$product) {
        $programDetails = $this->programDetailsRepository->getProgramDetailsByProduct($product);
        foreach ($programDetails as $programDetail) {
            if(!$product->getProductSelectedPrograms()->getPrograms()->contains($programDetail->getProgram()->getProduct())) {
                $this->programDetailsManager->remove($programDetail);
            }
        }
    }
}
