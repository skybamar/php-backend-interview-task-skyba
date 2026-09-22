<?php declare(strict_types=1);

namespace Tests\Unit\Order\Application\Fake;

use App\Order\Application\Port\Customers;
use App\Shared\Domain\Id\CustomerId;

final readonly class InMemoryCustomers implements Customers
{
	/**
	 * @param list<CustomerId> $existing
	 */
	public function __construct(
		private array $existing,
	) {
	}

	public function exists(CustomerId $customerId): bool
	{
		foreach ($this->existing as $existingId) {
			if ($existingId->equals($customerId)) {
				return true;
			}
		}

		return false;
	}
}
