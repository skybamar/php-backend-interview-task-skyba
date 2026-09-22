<?php declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\RuntimeException;

final class InvalidQuantity extends RuntimeException
{
	private function __construct(
		string $message,
		public readonly int $quantity,
	) {
		parent::__construct($message);
	}

	public static function create(int $quantity): self
	{
		return new self(\sprintf('Quantity must be at least 1, %d given.', $quantity), $quantity);
	}
}
