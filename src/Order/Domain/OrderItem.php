<?php declare(strict_types=1);

namespace App\Order\Domain;

use App\Shared\Domain\Id\ProductId;
use Brick\Math\BigDecimal;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
	#[ORM\Id]
	#[ORM\Column(type: 'order_item_id')]
	private OrderItemId $id;

	#[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
	#[ORM\JoinColumn(name: 'order_id', nullable: false)]
	private Order $order;

	#[ORM\Column(type: 'product_id')]
	private ProductId $productId;

	#[ORM\Embedded(class: Quantity::class, columnPrefix: false)]
	private Quantity $quantity;

	#[ORM\Column(type: 'big_decimal', precision: 10, scale: 2)]
	private BigDecimal $unitPrice;

	public function __construct(
		OrderItemId $id,
		Order $order,
		PricedItem $pricedItem,
	) {
		$this->id = $id;
		$this->order = $order;
		$this->productId = $pricedItem->productId;
		$this->quantity = $pricedItem->quantity;
		$this->unitPrice = $pricedItem->unitPrice;
	}

	public function getId(): OrderItemId
	{
		return $this->id;
	}

	public function getProductId(): ProductId
	{
		return $this->productId;
	}

	public function getQuantity(): Quantity
	{
		return $this->quantity;
	}

	public function getUnitPrice(): BigDecimal
	{
		return $this->unitPrice;
	}

	public function getLineTotal(): BigDecimal
	{
		return $this->unitPrice->multipliedBy($this->quantity->toInt());
	}
}
