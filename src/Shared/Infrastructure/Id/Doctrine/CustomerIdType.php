<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\Id\Doctrine;

use App\Shared\Domain\Id\CustomerId;

/**
 * @extends UuidIdType<CustomerId>
 */
final class CustomerIdType extends UuidIdType
{
	public const string NAME = 'customer_id';

	public function getName(): string
	{
		return self::NAME;
	}

	protected function getIdClassName(): string
	{
		return CustomerId::class;
	}
}
