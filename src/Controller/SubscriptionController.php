<?php

namespace Ksante\SubscriptionPlugin\Controller;

use Ksante\SubscriptionPlugin\Service\SubscriptionService;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends ResourceController
{
    /** @var SubscriptionService */
    protected $subscriptionService;


    public function __construct(SubscriptionService $subscriptionService) {
        $this->subscriptionService = $subscriptionService;
    }

    public function pauseSubscription(int $id, Request $request)
    {
        $this->subscriptionService->pauseSubscription($id);
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function unsubscribe(int $id, Request $request)
    {
        $this->subscriptionService->unsubscribe($id);
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function resendPaymentEmail(int $id, Request $request)
    {
        return $this->subscriptionService->resendPaymentEmail($id);
    }

    public function accessPayboxPage(int $id, Request $request)
    {
        return $this->subscriptionService->accessPayboxPage($id);
    }

    public function resumeSubscription(int $id, Request $request)
    {
        $this->subscriptionService->resumeSubscription($id);
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function updateNextOrderDate(Request $request)
    {
        $this->subscriptionService->updateNextOrderDate($request->request->all());
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function autoPauseSubscription(int $id, Request $request)
    {
        $this->subscriptionService->autoPauseSubscription($id);
        $parentRoute = $request->headers->get('referer');
        return $this->redirect($parentRoute);
    }

    public function csvExport(Request $request) {
        $fileContent = $this->subscriptionService->csvExport();
        $response = new Response($fileContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="subscriptions.csv"');

        return $response;
    }

    public function pauseSubscriptionByUser(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->subscriptionService->pauseSubscriptionByUser($parameters);
    }

    public function autoPauseSubscriptionByUser(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->subscriptionService->autoPauseSubscriptionByUser($parameters);
    }

    public function resumeSubscriptionByUser(Request $request) {
        //Getting the request parameters
        $parameters = $request->request->all();
        return $this->subscriptionService->resumeSubscriptionByUser($parameters);
    }

    public function getSubscriptionsByCustomerID(Request $request, int $id) {
        return $this->subscriptionService->getSubscriptionsByCustomerID($id);
    }
}
