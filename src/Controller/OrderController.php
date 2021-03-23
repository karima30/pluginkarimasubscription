<?php

namespace Ksante\SubscriptionPlugin\Controller;

use Ksante\SubscriptionPlugin\Service\OrderService;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends ResourceController
{
    /** @var OrderService */
    protected $orderService;

    public function __construct(OrderService $orderService) {
        $this->orderService = $orderService;
    }

    public function createOrderAlongWithSubscription(Request $request)
    {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->orderService->createOrderAlongWithSubscription($parameters);
    }

    public function generateOrderFromSubscriptionOrder(Request $request, $subscriptionID)
    {
        $this->orderService->generateOrderFromSubscriptionOrder($subscriptionID);
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }
}
