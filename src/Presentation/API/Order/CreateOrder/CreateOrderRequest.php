<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

/**
 * Loosely typed on purpose: every field is validated by CreateOrderCommandFactory
 * so each violation maps to the error code the API contract promises.
 */
final class CreateOrderRequest
{
	public mixed $customerId = null;

	public mixed $items = null;
}
