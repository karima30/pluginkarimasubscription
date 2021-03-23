<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\EventListener;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class NutritionalInformationImageListener
{
    /** @var ImageUploaderInterface */
    private $uploader;

    public function __construct(ImageUploaderInterface $uploader)
    {
        $this->uploader = $uploader;
    }

    public function upload(GenericEvent $event): void
    {
        $product = $event->getSubject();
        Assert::isInstanceOf($product, ProductInterface::class);
        foreach ($product->getTranslations() as $translation) {
            if(!empty($translation->getNutritionalInformationImage()) && $translation->getNutritionalInformationImage()->hasFile()) {
                $this->uploader->upload($translation->getNutritionalInformationImage());
            }
        }
    }
}
