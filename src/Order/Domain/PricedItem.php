<?php declare(strict_types=1);

namespace App\Order\Domain;

use App\Shared\Domain\Id\ProductId;
use Brick\Math\BigDecimal;

final readonly class PricedItem
{
	public function __construct(
		public ProductId $productId,
		public Quantity $quantity,
		public BigDecimal $unitPrice,
	) {
	}
}
