<?php declare(strict_types=1);

namespace App\Order\Infrastructure\Doctrine;

use App\Order\Domain\OrderId;
use App\Shared\Infrastructure\Id\Doctrine\UuidIdType;

/**
 * @extends UuidIdType<OrderId>
 */
final class OrderIdType extends UuidIdType
{
	public const string NAME = 'order_id';

	public function getName(): string
	{
		return self::NAME;
	}

	protected function getIdClassName(): string
	{
		return OrderId::class;
	}
}
