<?php declare(strict_types=1);

namespace App\Order\Application\CreateDraftOrder;

use App\Shared\Domain\Id\ProductId;

final readonly class RequestedItem
{
	public function __construct(
		public ProductId $productId,
		public int $quantity,
	) {
	}
}
