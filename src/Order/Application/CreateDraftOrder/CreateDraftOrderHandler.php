<?php declare(strict_types=1);

namespace App\Order\Application\CreateDraftOrder;

use App\Order\Application\Exception\CustomerNotFound;
use App\Order\Application\Exception\UnknownProduct;
use App\Order\Application\Port\Customers;
use App\Order\Application\Port\ProductPrices;
use App\Order\Domain\Exception\EmptyItems;
use App\Order\Domain\Exception\InvalidQuantity;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepository;
use App\Order\Domain\PricedItem;
use App\Order\Domain\Quantity;
use Brick\DateTime\Clock;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\TimeZone;

final readonly class CreateDraftOrderHandler
{
	public function __construct(
		private Customers $customers,
		private ProductPrices $productPrices,
		private OrderRepository $orders,
		private Clock $clock,
	) {
	}

	/**
	 * @throws CustomerNotFound
	 * @throws InvalidQuantity
	 * @throws UnknownProduct
	 * @throws EmptyItems
	 */
	public function handle(CreateDraftOrderCommand $command): Order
	{
		if ($this->customers->exists($command->customerId) === false) {
			throw CustomerNotFound::create($command->customerId);
		}

		$quantities = \array_map(static fn (RequestedItem $item) => Quantity::of($item->quantity), $command->items);
		$prices = $this->productPrices->forProducts(
			\array_map(static fn (RequestedItem $item) => $item->productId, $command->items),
		);

		$pricedItems = [];

		foreach ($command->items as $index => $item) {
			$unitPrice = $prices[$item->productId->toString()] ?? throw UnknownProduct::create($item->productId);
			$pricedItems[] = new PricedItem($item->productId, $quantities[$index], $unitPrice);
		}

		$order = Order::createDraft(
			OrderId::generate(),
			$command->customerId,
			LocalDateTime::now(TimeZone::utc(), $this->clock),
			$pricedItems,
		);
		$this->orders->add($order);

		return $order;
	}
}
