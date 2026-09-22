<?php declare(strict_types=1);

namespace App\Order\Application\CreateDraftOrder;

use App\Shared\Domain\Id\CustomerId;

final readonly class CreateDraftOrderCommand
{
	/**
	 * @param list<RequestedItem> $items
	 */
	public function __construct(
		public CustomerId $customerId,
		public array $items,
	) {
	}
}
