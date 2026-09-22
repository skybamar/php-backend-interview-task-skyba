<?php declare(strict_types=1);

namespace App\Order\Application\Port;

use App\Shared\Domain\Id\ProductId;
use Brick\Math\BigDecimal;

interface ProductPrices
{
	/**
	 * @param list<ProductId> $productIds
	 * @return array<string, BigDecimal> current price keyed by product id; unknown products are left out
	 */
	public function forProducts(array $productIds): array;
}
