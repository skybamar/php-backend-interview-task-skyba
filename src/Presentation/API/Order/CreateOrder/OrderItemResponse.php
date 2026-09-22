<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

final readonly class OrderItemResponse
{
	public function __construct(
		public string $id,
		public string $productId,
		public int $quantity,
		public string $unitPrice,
	) {
	}
}
