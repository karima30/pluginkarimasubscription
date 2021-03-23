<?php

namespace Ksante\SubscriptionPlugin\Menu\Product;

use Sylius\Bundle\AdminBundle\Event\ProductMenuBuilderEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class AdminProductFormMenuListener
{
    protected $currentRoute = "";

    public function setContainer(ContainerInterface $container = null)
    {
        $requestStack = $container->get('request_stack');
        $masterRequest = $requestStack->getMasterRequest();

        if ($masterRequest) {
            $this->currentRoute = $masterRequest->attributes->get('_route');
        }
    }

    public function addItems(ProductMenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        if (stripos($this->currentRoute, 'ksante_subscription_admin_product' ) !== false) {
            $menu
                ->addChild('program')
                ->setAttribute('template', '@KsanteSubscriptionPlugin/Admin/Product/Tab/_program.html.twig')
                ->setLabel('ksante_subscription.ui.program_details')
            ;
        } else {
            $menu
                ->addChild('productSelectedPrograms')
                ->setAttribute('template', '@KsanteSubscriptionPlugin/Admin/Product/Tab/_productSelectedPrograms.html.twig')
                ->setLabel('ksante_subscription.ui.product_selected_products')
            ;
        }
    }
}
