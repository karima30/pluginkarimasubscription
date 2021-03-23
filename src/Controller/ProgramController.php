<?php

namespace Ksante\SubscriptionPlugin\Controller;

use Ksante\SubscriptionPlugin\Service\ProgramService;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProgramController extends ResourceController
{
    /** @var ProgramService */
    protected $programService;
    /** @var UrlGeneratorInterface */
    protected $router;

    public function __construct(ProgramService $programService, UrlGeneratorInterface $router) {
        $this->programService = $programService;
        $this->router = $router;
    }

    public function duplicateProgram(int $id)
    {
        $this->programService->duplicateProgram($id);
        $programsPage = $this->router->generate('ksante_subscription_admin_product_index');
        return new RedirectResponse($programsPage);
    }
}
