<?php declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\CreateDraftOrder\CreateDraftOrderCommand;
use App\Order\Application\CreateDraftOrder\CreateDraftOrderHandler;
use App\Order\Application\CreateDraftOrder\RequestedItem;
use App\Order\Application\Exception\CustomerNotFound;
use App\Order\Application\Exception\UnknownProduct;
use App\Order\Domain\Exception\EmptyItems;
use App\Order\Domain\Exception\InvalidQuantity;
use App\Shared\Domain\Id\CustomerId;
use App\Shared\Domain\Id\ProductId;
use Brick\DateTime\Clock\FixedClock;
use Brick\DateTime\Instant;
use Brick\Math\BigDecimal;
use Tester\Assert;
use Tester\TestCase;
use Tests\Unit\Order\Application\Fake\InMemoryCustomers;
use Tests\Unit\Order\Application\Fake\InMemoryOrderRepository;
use Tests\Unit\Order\Application\Fake\InMemoryProductPrices;

require __DIR__ . '/../../../bootstrap.php';

final class CreateDraftOrderHandlerTest extends TestCase
{
	private CustomerId $alice;

	private ProductId $creatine;

	private ProductId $shaker;

	private InMemoryOrderRepository $orders;

	private CreateDraftOrderHandler $handler;

	protected function setUp(): void
	{
		$this->alice = CustomerId::generate();
		$this->creatine = ProductId::generate();
		$this->shaker = ProductId::generate();
		$this->orders = new InMemoryOrderRepository();
		$this->handler = new CreateDraftOrderHandler(
			new InMemoryCustomers([$this->alice]),
			new InMemoryProductPrices([
				$this->creatine->toString() => BigDecimal::of('399.00'),
				$this->shaker->toString() => BigDecimal::of('129.00'),
			]),
			$this->orders,
			new FixedClock(Instant::of(1_790_000_000)),
		);
	}

	public function testStoresDraftWithCurrentPricesAndClockTime(): void
	{
		$order = $this->handler->handle(new CreateDraftOrderCommand($this->alice, [
			new RequestedItem($this->creatine, 2),
			new RequestedItem($this->shaker, 1),
		]));

		Assert::same([$order], $this->orders->added);
		Assert::true($order->getCustomerId()->equals($this->alice));
		Assert::same('2026-09-21T14:13:20', (string)$order->getCreatedAt());
		Assert::true($order->getTotalAmount()->isEqualTo('927.00'));
		Assert::true($order->getItems()[0]->getUnitPrice()->isEqualTo('399.00'));
		Assert::true($order->getItems()[1]->getUnitPrice()->isEqualTo('129.00'));
	}

	public function testUnknownCustomerIsRejectedBeforeItemsAreInspected(): void
	{
		$stranger = CustomerId::generate();

		$exception = Assert::exception(
			fn () => $this->handler->handle(new CreateDraftOrderCommand($stranger, [])),
			CustomerNotFound::class,
		);

		\assert($exception instanceof CustomerNotFound);
		Assert::true($exception->customerId->equals($stranger));
		Assert::same([], $this->orders->added);
	}

	public function testEmptyItemsAreRejected(): void
	{
		Assert::exception(
			fn () => $this->handler->handle(new CreateDraftOrderCommand($this->alice, [])),
			EmptyItems::class,
		);
	}

	public function testFirstUnknownProductIsReported(): void
	{
		$firstUnknown = ProductId::generate();
		$secondUnknown = ProductId::generate();

		$exception = Assert::exception(
			fn () => $this->handler->handle(new CreateDraftOrderCommand($this->alice, [
				new RequestedItem($this->creatine, 1),
				new RequestedItem($firstUnknown, 1),
				new RequestedItem($secondUnknown, 1),
			])),
			UnknownProduct::class,
		);

		\assert($exception instanceof UnknownProduct);
		Assert::true($exception->productId->equals($firstUnknown));
	}

	public function testInvalidQuantityTakesPrecedenceOverUnknownProduct(): void
	{
		Assert::exception(
			fn () => $this->handler->handle(new CreateDraftOrderCommand($this->alice, [
				new RequestedItem(ProductId::generate(), 1),
				new RequestedItem($this->creatine, 0),
			])),
			InvalidQuantity::class,
		);
	}
}

(new CreateDraftOrderHandlerTest())->run();
