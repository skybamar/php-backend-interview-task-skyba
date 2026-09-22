<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

use App\Shared\Domain\Exception\RuntimeException;

final class InvalidRequest extends RuntimeException
{
	/**
	 * @param array<string, mixed> $details
	 */
	private function __construct(
		public readonly string $errorCode,
		public readonly array $details,
	) {
		parent::__construct($errorCode);
	}

	public static function invalidCustomerId(mixed $customerId): self
	{
		return new self('INVALID_CUSTOMER_ID', ['customer_id' => $customerId]);
	}

	public static function emptyItems(): self
	{
		return new self('EMPTY_ITEMS', []);
	}

	public static function invalidQuantity(mixed $quantity): self
	{
		return new self('INVALID_QUANTITY', ['quantity' => $quantity]);
	}

	public static function unknownProduct(mixed $productId): self
	{
		return new self('UNKNOWN_PRODUCT', ['product_id' => $productId]);
	}
}
