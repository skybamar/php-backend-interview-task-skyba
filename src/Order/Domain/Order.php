<?php declare(strict_types=1);

namespace App\Order\Domain;

use App\Order\Domain\Exception\EmptyItems;
use App\Shared\Domain\Id\CustomerId;
use Brick\DateTime\LocalDateTime;
use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
	#[ORM\Id]
	#[ORM\Column(type: 'order_id')]
	private OrderId $id;

	#[ORM\Column(type: 'customer_id')]
	private CustomerId $customerId;

	#[ORM\Column(type: 'string', length: 16, enumType: OrderStatus::class)]
	private OrderStatus $status;

	#[ORM\Column(type: 'big_decimal', precision: 10, scale: 2)]
	private BigDecimal $totalAmount;

	#[ORM\Column(type: 'local_date_time')]
	private LocalDateTime $createdAt;

	/** @var Collection<int, OrderItem> */
	#[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'])]
	private Collection $items;

	/**
	 * @param list<PricedItem> $pricedItems
	 * @throws EmptyItems
	 */
	private function __construct(
		OrderId $id,
		CustomerId $customerId,
		LocalDateTime $createdAt,
		array $pricedItems,
	) {
		if ($pricedItems === []) {
			throw EmptyItems::create();
		}

		$this->id = $id;
		$this->customerId = $customerId;
		$this->status = OrderStatus::DRAFT;
		$this->createdAt = $createdAt;
		$this->items = new ArrayCollection();

		foreach ($pricedItems as $pricedItem) {
			$this->items->add(new OrderItem(OrderItemId::generate(), $this, $pricedItem));
		}

		$this->totalAmount = $this->sumOfLineTotals();
	}

	/**
	 * @param list<PricedItem> $pricedItems
	 * @throws EmptyItems
	 */
	public static function createDraft(
		OrderId $id,
		CustomerId $customerId,
		LocalDateTime $createdAt,
		array $pricedItems,
	): self {
		return new self($id, $customerId, $createdAt, $pricedItems);
	}

	public function getId(): OrderId
	{
		return $this->id;
	}

	public function getCustomerId(): CustomerId
	{
		return $this->customerId;
	}

	public function getStatus(): OrderStatus
	{
		return $this->status;
	}

	public function getTotalAmount(): BigDecimal
	{
		return $this->totalAmount;
	}

	public function getCreatedAt(): LocalDateTime
	{
		return $this->createdAt;
	}

	/**
	 * @return list<OrderItem>
	 */
	public function getItems(): array
	{
		return $this->items->getValues();
	}

	private function sumOfLineTotals(): BigDecimal
	{
		$total = BigDecimal::zero();

		foreach ($this->items as $item) {
			$total = $total->plus($item->getLineTotal());
		}

		return $total;
	}
}
