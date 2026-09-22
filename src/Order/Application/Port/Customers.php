<?php declare(strict_types=1);

namespace App\Order\Application\Port;

use App\Shared\Domain\Id\CustomerId;

interface Customers
{
	public function exists(CustomerId $customerId): bool;
}
