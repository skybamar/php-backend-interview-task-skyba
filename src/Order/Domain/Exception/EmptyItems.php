<?php declare(strict_types=1);

namespace App\Order\Domain\Exception;

use App\Shared\Domain\Exception\RuntimeException;

final class EmptyItems extends RuntimeException
{
	private function __construct(string $message)
	{
		parent::__construct($message);
	}

	public static function create(): self
	{
		return new self('Order must contain at least one item.');
	}
}
