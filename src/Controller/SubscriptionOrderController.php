<?php

namespace Ksante\SubscriptionPlugin\Controller;

use Ksante\SubscriptionPlugin\Form\Type\SubscriptionOrderOptionsType;
use Ksante\SubscriptionPlugin\Form\Type\SubscriptionOrderProductsType;
use Ksante\SubscriptionPlugin\Service\SubscriptionOrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionOrderController extends AbstractController
{
    /** @var SubscriptionOrderService */
    protected $subscriptionOrderService;


    public function __construct(SubscriptionOrderService $subscriptionOrderService) {
        $this->subscriptionOrderService = $subscriptionOrderService;
    }

    public function updateSelectedProducts(int $id, Request $request)
    {
        $subscriptionOrderItem = $this->subscriptionOrderService->findProgramSubscriptionOrderItemBySubscriptionOrderId($id);

        $totalQuantity = $this->subscriptionOrderService->calculateTotalProductsQuantity($subscriptionOrderItem);

        $manageSelectedProductsForm = $this->createForm(SubscriptionOrderProductsType::class, $subscriptionOrderItem);

        return $this->render('@KsanteSubscriptionPlugin/Admin/SubscriptionOrder/SelectedProducts/manageSelectedProducts.html.twig', [
            'form'  =>  $manageSelectedProductsForm->createView(),
            'total' => $totalQuantity
        ]);
    }

    public function updateSelectedProductsPost($id, Request $request)
    {
        //Getting the request parameters
        $parameters = $request->request->all();
        if(!empty($parameters['sylius_subscription_order_products']['orderItemDetails'])) {
            $settingUpNewSelectedProductsList = $this->subscriptionOrderService->updateSubscriptionOrderSelectedProducts($id, $parameters['sylius_subscription_order_products']['orderItemDetails']);
            if($settingUpNewSelectedProductsList instanceof JsonResponse && $settingUpNewSelectedProductsList->getStatusCode() != Response::HTTP_CREATED) {
                //return $settingUpNewSelectedProductsList;
            }
        }

        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function updateSubscriptionOrderSelectedProducts(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->subscriptionOrderService->updateSubscriptionOrderSelectedProductsFromFront($parameters);
    }

    public function updateSelectedOptions(int $id, Request $request)
    {
        $subscriptionOrder = $this->subscriptionOrderService->getSubscriptionOrderById($id);
        $totalQuantity = $this->subscriptionOrderService->calculateTotalOptionsQuantity($subscriptionOrder);

        $manageSelectedOptionsForm = $this->createForm(SubscriptionOrderOptionsType::class, $subscriptionOrder);

        return $this->render('@KsanteSubscriptionPlugin/Admin/SubscriptionOrder/SelectedOptions/manageSelectedOptions.html.twig', [
            'form'  =>  $manageSelectedOptionsForm->createView(),
            'total' => $totalQuantity
        ]);
    }

    public function updateSelectedOptionsPost(int $id, Request $request)
    {
        //Getting the request parameters
        $parameters = $request->request->all();
        if(!empty($parameters['sylius_subscription_order_options']['optionsItems'])) {
            $settingUpNewSelectedOptionsList = $this->subscriptionOrderService->updateSubscriptionOrderSelectedOptions($id, $parameters['sylius_subscription_order_options']['optionsItems']);
            if($settingUpNewSelectedOptionsList instanceof JsonResponse && $settingUpNewSelectedOptionsList->getStatusCode() != Response::HTTP_CREATED) {
                //return $settingUpNewSelectedOptionsList;
            }
        }

        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function updateSubscriptionOrderSelectedOptions(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->subscriptionOrderService->updateSubscriptionOrderSelectedOptionsFromFront($parameters);
    }

    public function updateSubscriptionOrderCouponCode(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        $this->subscriptionOrderService->updateSubscriptionOrderCouponCode($parameters);

        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }
}
