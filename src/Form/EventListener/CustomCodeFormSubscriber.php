<?php


namespace Ksante\SubscriptionPlugin\Form\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CustomCodeFormSubscriber implements EventSubscriberInterface
{
    private $type;
    private $label;

    /**
     * @param string $type
     * @param string $label
     */
    public function __construct($type = TextType::class, $label = 'sylius.ui.code')
    {
        $this->type  = $type;
        $this->label = $label;
    }

    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $disabled = false;

        $form = $event->getForm();
        $form->add('code', $this->type, ['label' => $this->label, 'disabled' => $disabled]);
    }
}
