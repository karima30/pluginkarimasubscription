<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\EventListener;

use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SettingProgramDetailsListener
{
    /** @var ContainerInterface */
    protected $container;

    protected $productRepository;
    protected $productSelectedProgramFactory;
    protected $productManager;
    protected $productSelectedProgramManager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->productRepository = $container->get('sylius.repository.product');
        $this->productManager = $container->get('sylius.manager.product');
        $this->productSelectedProgramFactory = $container->get('ksante_subscription_plugin.factory.product_selected_programs');
        $this->productSelectedProgramManager = $container->get('ksante_subscription_plugin.manager.product_selected_programs');
    }

    public function settingProgramDetails(GenericEvent $event): void
    {
        $program = $event->getSubject();

        $this->addNewProductDetailsToTheSelectedPrograms($program);

        $this->removeUnnecessarySelectedPrograms($program);

        $this->productSelectedProgramManager->flush();
        $this->productManager->flush();
    }

    public function addNewProductDetailsToTheSelectedPrograms(ProductInterface &$program) {
        foreach ($program->getProgram()->getProgramDetails() as $productDetail) {
            if(empty($productDetail->getProduct()->getProductSelectedPrograms())) {
                $selectedPrograms = $this->productSelectedProgramFactory->createNew();
                $selectedPrograms->addProgram($program);
                $this->productSelectedProgramManager->persist($selectedPrograms);
                $productDetail->getProduct()->setProductSelectedPrograms($selectedPrograms);
            } else {
                $productDetail->getProduct()->getProductSelectedPrograms()->addProgram($program);
            }
            $this->productManager->persist($productDetail->getProduct());
        }
    }

    public function removeUnnecessarySelectedPrograms(ProductInterface &$program) {
        $productsBySelectedProgram = $this->productRepository->getProductsBySelectedProgram($program->getId());
        foreach ($productsBySelectedProgram as $productBySelectedProgram) {
            $isProgramFound = false;
            foreach ($program->getProgram()->getProgramDetails() as $productDetail) {
                if($productDetail->getProduct()->getId() == $productBySelectedProgram->getId()) {
                    $isProgramFound = true;
                }
            }
            if(!$isProgramFound) {
                $productBySelectedProgram->getProductSelectedPrograms()->removeProgram($program);
                $this->productManager->persist($productBySelectedProgram);
            }
        }
    }
}
