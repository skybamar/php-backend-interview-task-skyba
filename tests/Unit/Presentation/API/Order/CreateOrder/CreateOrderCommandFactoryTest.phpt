<?php declare(strict_types=1);

namespace Tests\Unit\Presentation\API\Order\CreateOrder;

use App\Presentation\API\Order\CreateOrder\CreateOrderCommandFactory;
use App\Presentation\API\Order\CreateOrder\CreateOrderRequest;
use App\Presentation\API\Order\CreateOrder\InvalidRequest;
use Ramsey\Uuid\Uuid;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../../../bootstrap.php';

final class CreateOrderCommandFactoryTest extends TestCase
{
	private CreateOrderCommandFactory $factory;

	protected function setUp(): void
	{
		$this->factory = new CreateOrderCommandFactory();
	}

	public function testBuildsCommandFromValidRequest(): void
	{
		$customerId = Uuid::uuid7()->toString();
		$productId = Uuid::uuid7()->toString();

		$command = $this->factory->fromRequest($this->request($customerId, [
			['product_id' => $productId, 'quantity' => 3],
		]));

		Assert::same($customerId, $command->customerId->toString());
		Assert::count(1, $command->items);
		Assert::same($productId, $command->items[0]->productId->toString());
		Assert::same(3, $command->items[0]->quantity->toInt());
	}

	/**
	 * @dataProvider provideInvalidRequests
	 */
	public function testRejectsInvalidRequestWithContractErrorCode(
		mixed $customerId,
		mixed $items,
		string $expectedCode,
	): void {
		$exception = Assert::exception(
			fn () => $this->factory->fromRequest($this->request($customerId, $items)),
			InvalidRequest::class,
		);

		\assert($exception instanceof InvalidRequest);
		Assert::same($expectedCode, $exception->errorCode);
	}

	/**
	 * @return array<string, array{mixed, mixed, string}>
	 */
	public function provideInvalidRequests(): array
	{
		$uuid = Uuid::uuid7()->toString();
		$validItems = [['product_id' => $uuid, 'quantity' => 1]];

		return [
			'missing customer id' => [null, $validItems, 'INVALID_CUSTOMER_ID'],
			'non-uuid customer id' => ['not-a-uuid', $validItems, 'INVALID_CUSTOMER_ID'],
			'numeric customer id' => [123, $validItems, 'INVALID_CUSTOMER_ID'],
			'missing items' => [$uuid, null, 'EMPTY_ITEMS'],
			'empty items' => [$uuid, [], 'EMPTY_ITEMS'],
			'items is an object' => [$uuid, ['product_id' => $uuid], 'EMPTY_ITEMS'],
			'item is not an object' => [$uuid, ['scalar'], 'UNKNOWN_PRODUCT'],
			'missing product id' => [$uuid, [['quantity' => 1]], 'UNKNOWN_PRODUCT'],
			'non-uuid product id' => [$uuid, [['product_id' => 'x', 'quantity' => 1]], 'UNKNOWN_PRODUCT'],
			'missing quantity' => [$uuid, [['product_id' => $uuid]], 'INVALID_QUANTITY'],
			'string quantity' => [$uuid, [['product_id' => $uuid, 'quantity' => '2']], 'INVALID_QUANTITY'],
			'float quantity' => [$uuid, [['product_id' => $uuid, 'quantity' => 1.5]], 'INVALID_QUANTITY'],
			'zero quantity' => [$uuid, [['product_id' => $uuid, 'quantity' => 0]], 'INVALID_QUANTITY'],
			'negative quantity' => [$uuid, [['product_id' => $uuid, 'quantity' => -1]], 'INVALID_QUANTITY'],
		];
	}

	private function request(mixed $customerId, mixed $items): CreateOrderRequest
	{
		$request = new CreateOrderRequest();
		$request->customerId = $customerId;
		$request->items = $items;

		return $request;
	}
}

(new CreateOrderCommandFactoryTest())->run();
