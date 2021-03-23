<?php


namespace Ksante\SubscriptionPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Sylius\Component\Core\Model\ProductInterface;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="sylius_order_item_details")
 * @ApiResource
 */
class OrderItemDetails implements OrderItemDetailsInterface
{
    use TimestampableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"order:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Product\Model\ProductVariantInterface")
     * @Groups({"order:read"})
     */
    protected $variant;

    /**
     * @ORM\ManyToOne (targetEntity="Sylius\Component\Order\Model\OrderItemInterface", inversedBy="orderItemDetails")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $orderItem;

    /**
     * @ORM\Column(type="integer", nullable = FALSE)
     * @Groups({"order:read"})
     */
    protected $quantity = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Sylius\Component\Taxonomy\Model\TaxonInterface")
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
