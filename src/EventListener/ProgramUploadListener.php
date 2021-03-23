<?php

declare(strict_types=1);

namespace Ksante\SubscriptionPlugin\EventListener;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class ProgramUploadListener
{
    /** @var ImageUploaderInterface */
    private $uploader;

    public function __construct(ImageUploaderInterface $uploader)
    {
        $this->uploader = $uploader;
    }

    public function upload(GenericEvent $event): void
    {
        $program = $event->getSubject();
        Assert::isInstanceOf($program, ProductInterface::class);
        foreach ($program->getImages() as $image) {
            if($image->hasFile()) {
                $this->uploader->upload($image);
            } else {
                $program->removeImage($image);
            }
        }
    }
}
