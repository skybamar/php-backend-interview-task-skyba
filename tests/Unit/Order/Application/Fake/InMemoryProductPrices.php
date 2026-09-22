<?php declare(strict_types=1);

namespace Tests\Unit\Order\Application\Fake;

use App\Order\Application\Port\ProductPrices;
use Brick\Math\BigDecimal;

final readonly class InMemoryProductPrices implements ProductPrices
{
	/**
	 * @param array<string, BigDecimal> $prices
	 */
	public function __construct(
		private array $prices,
	) {
	}

	public function forProducts(array $productIds): array
	{
		$found = [];

		foreach ($productIds as $productId) {
			$key = $productId->toString();

			if (isset($this->prices[$key])) {
				$found[$key] = $this->prices[$key];
			}
		}

		return $found;
	}
}
