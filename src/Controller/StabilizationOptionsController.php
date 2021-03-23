<?php

namespace Ksante\SubscriptionPlugin\Controller;

use Ksante\SubscriptionPlugin\Service\StabilizationOptionsService;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StabilizationOptionsController extends ResourceController
{
    /** @var StabilizationOptionsService */
    protected $stabilizationOptionsService;

    /** @var UrlGeneratorInterface */
    protected $router;

    public function __construct(UrlGeneratorInterface $router, StabilizationOptionsService $stabilizationOptionsService) {
        $this->router = $router;
        $this->stabilizationOptionsService = $stabilizationOptionsService;
    }

    public function updateStabilizationOptionPrice(Request $request)
    {
        $this->stabilizationOptionsService->updateStabilizationOptionPrice($request->request->all());
        $stabilizationOptionsPage = $this->router->generate('ksante_subscription_admin_stabilization_options_index');
        return new RedirectResponse($stabilizationOptionsPage);
    }

    public function generateStabilizationOptions(Request $request) {
        $this->stabilizationOptionsService->generateStabilizationOptions();
        $stabilizationOptionsPage = $this->router->generate('ksante_subscription_admin_stabilization_options_index');
        return new RedirectResponse($stabilizationOptionsPage);
    }

    public function getStabilizationOptionsByCustomer(Request $request, int $id) {
        return $this->stabilizationOptionsService->getStabilizationOptionsByCustomer($id);
    }
}
