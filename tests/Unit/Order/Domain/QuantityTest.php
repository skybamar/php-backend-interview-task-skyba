<?php declare(strict_types=1);

namespace Tests\Unit\Order\Domain;

use App\Order\Domain\Exception\InvalidQuantity;
use App\Order\Domain\Quantity;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

final class QuantityTest extends TestCase
{
	public function testAcceptsOneAndMore(): void
	{
		Assert::same(1, Quantity::of(1)->toInt());
		Assert::same(250, Quantity::of(250)->toInt());
	}

	public function testRejectsZeroAndNegative(): void
	{
		Assert::exception(static fn () => Quantity::of(0), InvalidQuantity::class);
		Assert::exception(static fn () => Quantity::of(-3), InvalidQuantity::class);
	}
}

(new QuantityTest())->run();
