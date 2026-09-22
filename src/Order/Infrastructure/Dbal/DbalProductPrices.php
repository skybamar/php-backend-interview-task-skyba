<?php declare(strict_types=1);

namespace App\Order\Infrastructure\Dbal;

use App\Order\Application\Port\ProductPrices;
use App\Shared\Domain\Id\ProductId;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DbalProductPrices implements ProductPrices
{
	public function __construct(
		private Connection $connection,
	) {
	}

	public function forProducts(array $productIds): array
	{
		if ($productIds === []) {
			return [];
		}

		$rows = $this->connection->fetchAllKeyValue(
			'SELECT id, price FROM products WHERE id IN (:ids)',
			['ids' => \array_map(static fn (ProductId $id) => $id->toString(), $productIds)],
			['ids' => ArrayParameterType::STRING],
		);

		$prices = [];

		foreach ($rows as $id => $price) {
			$prices[(string)$id] = BigDecimal::of((string)$price);
		}

		return $prices;
	}
}
