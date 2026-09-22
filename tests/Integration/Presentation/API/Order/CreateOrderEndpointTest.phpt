<?php declare(strict_types=1);

namespace Tests\Integration\Presentation\API\Order;

use App\Bootstrap;
use Doctrine\DBAL\Connection;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Slim\App;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../../bootstrap.php';

/**
 * Runs the real HTTP stack against the seeded database; every test runs inside
 * a transaction that is rolled back so the seed stays untouched.
 */
final class CreateOrderEndpointTest extends TestCase
{
	/** @var App<\Psr\Container\ContainerInterface> */
	private App $app;

	private Connection $connection;

	private string $alice;

	/** @var array<string, string> */
	private array $products;

	protected function setUp(): void
	{
		$container = Bootstrap::bootForApi()->createContainer();
		$this->app = $container->getByType(App::class);
		$this->connection = $container->getByType(Connection::class);
		$this->connection->beginTransaction();

		$this->alice = (string)$this->connection->fetchOne('SELECT id FROM customers WHERE email = ?', ['alice@example.com']);
		$this->products = \array_map(
			static fn (mixed $id) => (string)$id,
			$this->connection->fetchAllKeyValue('SELECT name, id FROM products'),
		);
	}

	protected function tearDown(): void
	{
		$this->connection->rollBack();
	}

	public function testCreatesDraftOrderWithPriceSnapshot(): void
	{
		$response = $this->post([
			'customer_id' => $this->alice,
			'items' => [
				['product_id' => $this->products['Creatine 500g'], 'quantity' => 2],
				['product_id' => $this->products['Shaker'], 'quantity' => 1],
			],
		]);

		Assert::same(201, $response->getStatusCode());
		$body = $this->decode($response);
		Assert::true(Uuid::isValid($body['id']));
		Assert::same($this->alice, $body['customer_id']);
		Assert::same('draft', $body['status']);
		Assert::same('927.00', $body['total_amount']);
		Assert::match('%d%-%d%-%d%T%d%:%d%:%d%%a?%Z', $body['created_at']);
		Assert::count(2, $body['items']);
		Assert::same($this->products['Creatine 500g'], $body['items'][0]['product_id']);
		Assert::same(2, $body['items'][0]['quantity']);
		Assert::same('399.00', $body['items'][0]['unit_price']);

		$stored = $this->connection->fetchAssociative('SELECT status, total_amount FROM orders WHERE id = ?', [$body['id']]);
		Assert::same(['status' => 'draft', 'total_amount' => '927.00'], $stored);
		Assert::same(2, (int)$this->connection->fetchOne('SELECT COUNT(*) FROM order_items WHERE order_id = ?', [$body['id']]));
	}

	public function testMalformedJsonIsBadRequest(): void
	{
		$response = $this->postRaw('{"customer_id": ');

		$this->assertError($response, 400, 'INVALID_JSON');
	}

	public function testMissingJsonContentTypeIsBadRequest(): void
	{
		$request = (new ServerRequestFactory())->createServerRequest('POST', '/api/orders')
			->withBody((new StreamFactory())->createStream('{}'));

		$this->assertError($this->app->handle($request), 400, 'INVALID_JSON');
	}

	public function testInvalidCustomerIdIsUnprocessable(): void
	{
		$response = $this->post(['customer_id' => 'nope', 'items' => [['product_id' => $this->products['Shaker'], 'quantity' => 1]]]);

		$this->assertError($response, 422, 'INVALID_CUSTOMER_ID');
	}

	public function testUnknownCustomerIsNotFound(): void
	{
		$stranger = Uuid::uuid7()->toString();
		$response = $this->post(['customer_id' => $stranger, 'items' => [['product_id' => $this->products['Shaker'], 'quantity' => 1]]]);

		$this->assertError($response, 404, 'CUSTOMER_NOT_FOUND');
		Assert::same($stranger, $this->decode($response)['error']['customer_id']);
	}

	public function testEmptyItemsAreUnprocessable(): void
	{
		$this->assertError($this->post(['customer_id' => $this->alice, 'items' => []]), 422, 'EMPTY_ITEMS');
		$this->assertError($this->post(['customer_id' => $this->alice]), 422, 'EMPTY_ITEMS');
	}

	public function testInvalidQuantityIsUnprocessable(): void
	{
		$response = $this->post(['customer_id' => $this->alice, 'items' => [['product_id' => $this->products['Shaker'], 'quantity' => 0]]]);

		$this->assertError($response, 422, 'INVALID_QUANTITY');
	}

	public function testFirstUnknownProductIsReported(): void
	{
		$unknown = Uuid::uuid7()->toString();
		$response = $this->post(['customer_id' => $this->alice, 'items' => [
			['product_id' => $this->products['Shaker'], 'quantity' => 1],
			['product_id' => $unknown, 'quantity' => 1],
			['product_id' => Uuid::uuid7()->toString(), 'quantity' => 1],
		]]);

		$this->assertError($response, 422, 'UNKNOWN_PRODUCT');
		Assert::same($unknown, $this->decode($response)['error']['product_id']);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function post(array $payload): ResponseInterface
	{
		return $this->postRaw(Json::encode($payload));
	}

	private function postRaw(string $body): ResponseInterface
	{
		$request = (new ServerRequestFactory())->createServerRequest('POST', '/api/orders')
			->withHeader('Content-Type', 'application/json')
			->withBody((new StreamFactory())->createStream($body));

		return $this->app->handle($request);
	}

	private function assertError(
		ResponseInterface $response,
		int $status,
		string $code,
	): void {
		Assert::same($status, $response->getStatusCode());
		Assert::same($code, $this->decode($response)['code']);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decode(ResponseInterface $response): array
	{
		$decoded = Json::decode((string)$response->getBody(), true);
		\assert(\is_array($decoded));

		return $decoded;
	}
}

(new CreateOrderEndpointTest())->run();
