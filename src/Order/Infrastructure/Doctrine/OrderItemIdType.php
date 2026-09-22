<?php declare(strict_types=1);

namespace App\Order\Infrastructure\Doctrine;

use App\Order\Domain\OrderItemId;
use App\Shared\Infrastructure\Id\Doctrine\UuidIdType;

/**
 * @extends UuidIdType<OrderItemId>
 */
final class OrderItemIdType extends UuidIdType
{
	public const string NAME = 'order_item_id';

	public function getName(): string
	{
		return self::NAME;
	}

	protected function getIdClassName(): string
	{
		return OrderItemId::class;
	}
}
