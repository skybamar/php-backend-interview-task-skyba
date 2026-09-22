<?php declare(strict_types=1);

namespace App\Presentation\API\Order\CreateOrder;

use App\Order\Application\CreateDraftOrder\CreateDraftOrderHandler;
use App\Order\Application\Exception\CustomerNotFound;
use App\Order\Application\Exception\UnknownProduct;
use App\Order\Domain\Exception\EmptyItems;
use App\Presentation\Shared\Http\Error\ErrorResponseFactory;
use App\Presentation\Shared\Http\Json\JsonRequestWithParsedBodyHandler;
use App\Presentation\Shared\Http\Json\JsonResponseFactory;
use App\Presentation\Shared\Http\StatusCode;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[OA\Post(
	path: '/api/orders',
	operationId: 'createOrder',
	summary: 'Create a draft order',
	requestBody: new OA\RequestBody(
		required: true,
		content: new OA\JsonContent(
			required: ['customer_id', 'items'],
			properties: [
				new OA\Property(property: 'customer_id', type: 'string', format: 'uuid'),
				new OA\Property(
					property: 'items',
					type: 'array',
					minItems: 1,
					items: new OA\Items(
						required: ['product_id', 'quantity'],
						properties: [
							new OA\Property(property: 'product_id', type: 'string', format: 'uuid'),
							new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
						],
						type: 'object',
					),
				),
			],
			type: 'object',
		),
	),
	tags: ['Orders'],
	responses: [
		new OA\Response(response: 201, description: 'Draft order created', content: new OA\JsonContent(ref: OrderResponse::class)),
		new OA\Response(response: 400, description: 'INVALID_JSON'),
		new OA\Response(response: 404, description: 'CUSTOMER_NOT_FOUND'),
		new OA\Response(response: 422, description: 'INVALID_CUSTOMER_ID, EMPTY_ITEMS, INVALID_QUANTITY, UNKNOWN_PRODUCT'),
	],
)]
final readonly class CreateOrderHandler implements JsonRequestWithParsedBodyHandler
{
	public function __construct(
		private CreateOrderCommandFactory $commandFactory,
		private CreateDraftOrderHandler $createDraftOrder,
		private JsonResponseFactory $jsonResponseFactory,
		private ErrorResponseFactory $errorResponseFactory,
	) {
	}

	public static function getParsedBodyClassName(): string
	{
		return CreateOrderRequest::class;
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$body = $request->getParsedBody();

		if ($body instanceof CreateOrderRequest === false) {
			return $this->errorResponseFactory->createErrorResponse(StatusCode::BAD_REQUEST, 'INVALID_JSON', []);
		}

		try {
			$order = $this->createDraftOrder->handle($this->commandFactory->fromRequest($body));
		} catch (InvalidRequest $e) {
			return $this->errorResponseFactory->createErrorResponse(StatusCode::UNPROCESSABLE_ENTITY, $e->errorCode, $e->details);
		} catch (CustomerNotFound $e) {
			return $this->errorResponseFactory->createErrorResponse(
				StatusCode::NOT_FOUND,
				'CUSTOMER_NOT_FOUND',
				['customer_id' => $e->customerId->toString()],
			);
		} catch (UnknownProduct $e) {
			return $this->errorResponseFactory->createErrorResponse(
				StatusCode::UNPROCESSABLE_ENTITY,
				'UNKNOWN_PRODUCT',
				['product_id' => $e->productId->toString()],
			);
		} catch (EmptyItems) {
			return $this->errorResponseFactory->createErrorResponse(StatusCode::UNPROCESSABLE_ENTITY, 'EMPTY_ITEMS', []);
		}

		return $this->jsonResponseFactory->create(OrderResponse::fromOrder($order), StatusCode::CREATED);
	}
}
