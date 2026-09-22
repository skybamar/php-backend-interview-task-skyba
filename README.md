## Quick start
Requirements: Docker + Docker Compose

1) `make reset`
2) API: http://localhost:8000

## Development

| Command | What it does |
| --- | --- |
| `make test` | Nette Tester (`tests/`), unit and integration tests |
| `make phpstan` | PHPStan level 8 with strict rules |
| `make phpcs` / `make phpcs-fix` | PSR + Slevomat coding standard |

Integration tests run against the seeded Postgres from `docker-compose.yml`; each test
runs inside a transaction that is rolled back, so the seed data stay intact.

## API

### `POST /api/orders`

Creates an order in the `draft` state. Product prices are snapshotted into the order
items at creation time.

```json
{
  "customer_id": "uuid",
  "items": [
    { "product_id": "uuid", "quantity": 2 }
  ]
}
```

Response `201 Created`:

```json
{
  "id": "uuid",
  "customer_id": "uuid",
  "status": "draft",
  "total_amount": "927.00",
  "created_at": "2026-09-22T13:50:17.457372Z",
  "items": [
    { "id": "uuid", "product_id": "uuid", "quantity": 2, "unit_price": "399.00" }
  ]
}
```

Amounts are strings with two decimals so no precision is lost in floats. `created_at` is
ISO 8601 in UTC.

Errors have the shape `{ "code": "...", "error": { ...details } }`:

| Status | Code | When |
| --- | --- | --- |
| 400 | `INVALID_JSON` | body is not valid JSON, or `Content-Type` is not `application/json` |
| 422 | `INVALID_CUSTOMER_ID` | `customer_id` is missing or not a UUID |
| 422 | `EMPTY_ITEMS` | `items` is missing, not a list, or empty |
| 422 | `INVALID_QUANTITY` | `quantity` is not an integer >= 1 |
| 422 | `UNKNOWN_PRODUCT` | first `product_id` that is not a UUID or does not exist |
| 404 | `CUSTOMER_NOT_FOUND` | customer does not exist |

When a request has several problems, shape errors (422) are reported first, then the
customer lookup (404), then product lookups (422). Duplicate products in `items` are kept
as separate lines; merging them is a business decision the assignment does not make.

## Architecture

Hexagonal layout per bounded context, currently `src/Order`:

- `Domain` – the `Order` aggregate with `OrderItem`, `Quantity`, `OrderStatus` and the
  invariants (at least one item, quantity >= 1). Doctrine mapping lives in attributes on
  the entities: a pragmatic trade-off, since the skeleton already scans `src/` for mappings.
- `Application` – the `CreateDraftOrder` use case and its outbound ports (`Customers`,
  `ProductPrices`, `OrderRepository`). Tested with in-memory fakes.
- `Infrastructure` – Doctrine repository and thin DBAL adapters over `customers` and
  `products`. No Customer/Product entities exist yet because nothing needs them.
- `src/Presentation/API/Order` – Slim handler, request DTO, command factory (maps each
  validation failure to its contract error code) and response DTO.

The request DTO is deliberately loosely typed and validated by hand: a strictly typed DTO
would fail inside the Symfony serializer and the contract needs a specific code per field.

## Decisions and known limits

- **No currency.** The schema has no currency column, so prices are plain decimals
  (`BigDecimal`); the shared `Money` value object is unused until currency exists in data.
- **`total_amount` is set at draft creation.** The seed keeps it `NULL` for drafts, so
  loading seeded drafts through the `Order` entity is not supported yet; revisit when an
  order-reading use case arrives.
- **`order_items.unit_price` is nullable in the schema** but required by the domain. The
  seed's intentionally broken row is left as is.
- Empty error details serialize as `[]` rather than `{}` (serializer quirk of the skeleton).
- Only `/api/orders` uses the `/api` prefix; the skeleton's `/hello` was left untouched.
- Nette DI autowired `ObjectNormalizer` with a property-info extractor without access
  extractors, so request bodies never deserialized; fixed in `config.global.neon`.
  JSON keys are snake_case via a name converter, PHP properties stay camelCase.
- `DoctrineIdTypeExtension` now rebuilds its class index on container compile; before,
  id types added after the first build were never registered.
