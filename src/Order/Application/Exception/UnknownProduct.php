<?php declare(strict_types=1);

namespace App\Order\Application\Exception;

use App\Shared\Domain\Exception\RuntimeException;
use App\Shared\Domain\Id\ProductId;

final class UnknownProduct extends RuntimeException
{
	private function __construct(
		string $message,
		public readonly ProductId $productId,
	) {
		parent::__construct($message);
	}

	public static function create(ProductId $productId): self
	{
		return new self(\sprintf('Product "%s" does not exist.', $productId), $productId);
	}
}
