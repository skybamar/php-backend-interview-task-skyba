<?php declare(strict_types=1);

namespace Tests\Unit\Order\Domain;

use App\Order\Domain\Exception\EmptyItems;
use App\Order\Domain\Order;
use App\Order\Domain\OrderId;
use App\Order\Domain\OrderStatus;
use App\Order\Domain\PricedItem;
use App\Order\Domain\Quantity;
use App\Shared\Domain\Id\CustomerId;
use App\Shared\Domain\Id\ProductId;
use Brick\DateTime\LocalDateTime;
use Brick\Math\BigDecimal;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

final class OrderTest extends TestCase
{
	public function testDraftIsCreatedWithSnapshotPricesAndTotal(): void
	{
		$createdAt = LocalDateTime::of(2026, 9, 22, 14, 5, 11);
		$creatine = ProductId::generate();
		$shaker = ProductId::generate();

		$order = Order::createDraft(OrderId::generate(), CustomerId::generate(), $createdAt, [
			new PricedItem($creatine, Quantity::of(2), BigDecimal::of('399.00')),
			new PricedItem($shaker, Quantity::of(1), BigDecimal::of('129.00')),
		]);

		Assert::same(OrderStatus::DRAFT, $order->getStatus());
		Assert::true($order->getCreatedAt()->isEqualTo($createdAt));
		Assert::true($order->getTotalAmount()->isEqualTo('927.00'));

		$items = $order->getItems();
		Assert::count(2, $items);
		Assert::true($items[0]->getProductId()->equals($creatine));
		Assert::same(2, $items[0]->getQuantity()->toInt());
		Assert::true($items[0]->getUnitPrice()->isEqualTo('399.00'));
		Assert::true($items[0]->getLineTotal()->isEqualTo('798.00'));
	}

	public function testItemsAreKeptInRequestedOrderWithoutMerging(): void
	{
		$product = ProductId::generate();

		$order = Order::createDraft(OrderId::generate(), CustomerId::generate(), LocalDateTime::of(2026, 1, 1, 0, 0), [
			new PricedItem($product, Quantity::of(1), BigDecimal::of('10.00')),
			new PricedItem($product, Quantity::of(3), BigDecimal::of('10.00')),
		]);

		Assert::count(2, $order->getItems());
		Assert::true($order->getTotalAmount()->isEqualTo('40.00'));
	}

	public function testDraftWithoutItemsIsRejected(): void
	{
		Assert::exception(
			static fn () => Order::createDraft(OrderId::generate(), CustomerId::generate(), LocalDateTime::of(2026, 1, 1, 0, 0), []),
			EmptyItems::class,
		);
	}
}

(new OrderTest())->run();
