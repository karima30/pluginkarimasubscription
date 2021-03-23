<?php

namespace Ksante\SubscriptionPlugin\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class EmailingService
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;
    protected $renderer;

    public function __construct(ContainerInterface $container, \Swift_Mailer $mailer, Environment $renderer) {
        $this->container = $container;
        $this->mailer = $mailer;
        $this->renderer = $renderer;
    }

    public function sendSubscriptionEmailToCustomer($templatePath, $customerAddress, $subject, $emailParameters) {
        $transporter = new \Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl');
        $transporter->setUsername($_SERVER['EMAIL_ACCOUNT_LOGIN'])
            ->setPassword($_SERVER['EMAIL_ACCOUNT_PASSWORD']);

        $mailer = new \Swift_Mailer($transporter);

        $messageObject = new \Swift_Message();

        $messageObject->setSubject($subject)
            ->setFrom("do-not-reply@example.com")
            ->setTo($customerAddress)
            ->setBody($this->renderer->render(
                $templatePath, $emailParameters
            ), 'text/html');
        $emailRes = $mailer->send($messageObject);

        return ($emailRes == 1) ? Response::HTTP_ACCEPTED : Response::HTTP_FORBIDDEN;

    }

}
