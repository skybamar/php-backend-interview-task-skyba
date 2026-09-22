<?php declare(strict_types=1);

namespace App\Order\Infrastructure\Dbal;

use App\Order\Application\Port\Customers;
use App\Shared\Domain\Id\CustomerId;
use Doctrine\DBAL\Connection;

final readonly class DbalCustomers implements Customers
{
	public function __construct(
		private Connection $connection,
	) {
	}

	public function exists(CustomerId $customerId): bool
	{
		return $this->connection->fetchOne(
			'SELECT 1 FROM customers WHERE id = :id',
			['id' => $customerId->toString()],
		) !== false;
	}
}
