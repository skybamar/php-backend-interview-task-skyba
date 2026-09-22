<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\Id\Doctrine;

use App\Shared\Domain\Id\ProductId;

/**
 * @extends UuidIdType<ProductId>
 */
final class ProductIdType extends UuidIdType
{
	public const string NAME = 'product_id';

	public function getName(): string
	{
		return self::NAME;
	}

	protected function getIdClassName(): string
	{
		return ProductId::class;
	}
}
