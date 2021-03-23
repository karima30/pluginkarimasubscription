<?php


namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Sylius\Component\Core\Model\ProductInterface;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_subscription_order_item_details")
 * @ApiResource
 */
class SubscriptionOrderItemDetails implements OrderItemDetailsInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"subscription_order"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Product\Model\ProductVariantInterface")
     * @Groups({"subscription_order"})
     */
    protected $variant;

    /**
     * @ORM\ManyToOne (targetEntity="Ksante\SubscriptionPlugin\Entity\SubscriptionOrderItem")
     * @ORM\JoinColumn(name="order_item_id", referencedColumnName="id")
     */
    protected $orderItem;

    /**
     * @ORM\Column(type="integer", nullable = FALSE)
     * @Groups({"subscription_order"})
     */
    protected $quantity = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
     * @Groups({"subscription_order"})
     */
    private $taxon;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVariant()
    {
        return $this->variant;
    }

    public function setVariant($variant): void
    {
        $this->variant = $variant;
    }

    public function getProduct(): ?ProductInterface
    {
        return $this->variant->getProduct();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getOrderItem()
    {
        return $this->orderItem;
    }

    public function setOrderItem($orderItem): void
    {
        $this->orderItem = $orderItem;
    }

    /**
     * @return TaxonInterface
     */
    public function getTaxon()
    {
        return $this->taxon;
    }

    /**
     * @param TaxonInterface $taxon
     */
    public function setTaxon($taxon): void
    {
        $this->taxon = $taxon;
    }

}
