<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

use App\Order\Domain\Order;
use App\Order\Domain\OrderItem;
use Brick\DateTime\TimeZone;

final readonly class OrderResponse
{
	private const int AMOUNT_SCALE = 2;

	/**
	 * @param list<OrderItemResponse> $items
	 */
	private function __construct(
		public string $id,
		public string $customerId,
		public string $status,
		public string $totalAmount,
		public string $createdAt,
		public array $items,
	) {
	}

	public static function fromOrder(Order $order): self
	{
		return new self(
			$order->getId()->toString(),
			$order->getCustomerId()->toString(),
			$order->getStatus()->value,
			(string)$order->getTotalAmount()->toScale(self::AMOUNT_SCALE),
			$order->getCreatedAt()->atTimeZone(TimeZone::utc())->getInstant()->toISOString(),
			\array_map(self::item(...), $order->getItems()),
		);
	}

	private static function item(OrderItem $item): OrderItemResponse
	{
		return new OrderItemResponse(
			$item->getId()->toString(),
			$item->getProductId()->toString(),
			$item->getQuantity()->toInt(),
			(string)$item->getUnitPrice()->toScale(self::AMOUNT_SCALE),
		);
	}
}
