<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

use App\Order\Application\CreateDraftOrder\CreateDraftOrderCommand;
use App\Order\Application\CreateDraftOrder\RequestedItem;
use App\Order\Domain\Exception\InvalidQuantity;
use App\Order\Domain\Quantity;
use App\Shared\Domain\Id\CustomerId;
use App\Shared\Domain\Id\ProductId;
use Ramsey\Uuid\Uuid;

final readonly class CreateOrderCommandFactory
{
	/**
	 * @throws InvalidRequest
	 */
	public function fromRequest(CreateOrderRequest $request): CreateDraftOrderCommand
	{
		if (\is_string($request->customerId) === false || Uuid::isValid($request->customerId) === false) {
			throw InvalidRequest::invalidCustomerId($request->customerId);
		}

		if (\is_array($request->items) === false || \array_is_list($request->items) === false || $request->items === []) {
			throw InvalidRequest::emptyItems();
		}

		return new CreateDraftOrderCommand(
			CustomerId::fromString($request->customerId),
			\array_map($this->requestedItem(...), $request->items),
		);
	}

	/**
	 * @throws InvalidRequest
	 */
	private function requestedItem(mixed $item): RequestedItem
	{
		$productId = \is_array($item) ? $item['product_id'] ?? null : null;
		$quantity = \is_array($item) ? $item['quantity'] ?? null : null;

		if (\is_string($productId) === false || Uuid::isValid($productId) === false) {
			throw InvalidRequest::unknownProduct($productId);
		}

		if (\is_int($quantity) === false) {
			throw InvalidRequest::invalidQuantity($quantity);
		}

		try {
			return new RequestedItem(ProductId::fromString($productId), Quantity::of($quantity));
		} catch (InvalidQuantity) {
			throw InvalidRequest::invalidQuantity($quantity);
		}
	}
}
