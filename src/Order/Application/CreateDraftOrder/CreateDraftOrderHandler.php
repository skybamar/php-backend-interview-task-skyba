<?php declare(strict_types=1);

namespace App\Order\Application\CreateDraftOrder;

use App\Order\Application\Exception\CustomerNotFound;
use App\Order\Application\Exception\UnknownProduct;
use App\Order\Application\Port\Customers;
use App\Order\Application\Port\ProductPrices;
use App\Order\Domain\Exception\EmptyItems;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderRepository;
use App\Order\Domain\PricedItem;
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
	 * @throws UnknownProduct
	 * @throws EmptyItems
	 */
	public function handle(CreateDraftOrderCommand $command): Order
	{
		if ($this->customers->exists($command->customerId) === false) {
			throw CustomerNotFound::create($command->customerId);
		}

		$prices = $this->productPrices->forProducts(
			\array_map(static fn (RequestedItem $item) => $item->productId, $command->items),
		);

		$pricedItems = [];

		foreach ($command->items as $item) {
			$unitPrice = $prices[$item->productId->toString()] ?? throw UnknownProduct::create($item->productId);
			$pricedItems[] = new PricedItem($item->productId, $item->quantity, $unitPrice);
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
