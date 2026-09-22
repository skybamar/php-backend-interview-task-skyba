<?php declare(strict_types=1);

namespace Tests\Unit\Order\Application\Fake;

use App\Order\Domain\Order;
use App\Order\Domain\OrderRepository;

final class InMemoryOrderRepository implements OrderRepository
{
	/** @var list<Order> */
	public array $added = [];

	public function add(Order $order): void
	{
		$this->added[] = $order;
	}
}
