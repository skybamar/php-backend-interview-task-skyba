<?php declare(strict_types=1);

namespace App\Order\Domain;

interface OrderRepository
{
	public function add(Order $order): void;
}
