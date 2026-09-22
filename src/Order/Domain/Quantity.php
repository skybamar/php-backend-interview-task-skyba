<?php declare(strict_types=1);

namespace App\Order\Domain;

use App\Order\Domain\Exception\InvalidQuantity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Quantity
{
	private function __construct(
		#[ORM\Column(name: 'quantity', type: 'integer')]
		private int $value,
	) {
	}

	/**
	 * @throws InvalidQuantity
	 */
	public static function of(int $value): self
	{
		if ($value < 1) {
			throw InvalidQuantity::create($value);
		}

		return new self($value);
	}

	/**
	 * @return positive-int
	 */
	public function toInt(): int
	{
		\assert($this->value >= 1);

		return $this->value;
	}
}
