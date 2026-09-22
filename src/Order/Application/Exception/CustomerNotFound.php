<?php declare(strict_types=1);

namespace App\Order\Application\Exception;

use App\Shared\Domain\Exception\RuntimeException;
use App\Shared\Domain\Id\CustomerId;

final class CustomerNotFound extends RuntimeException
{
	private function __construct(
		string $message,
		public readonly CustomerId $customerId,
	) {
		parent::__construct($message);
	}

	public static function create(CustomerId $customerId): self
	{
		return new self(\sprintf('Customer "%s" was not found.', $customerId), $customerId);
	}
}
