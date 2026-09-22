<?php declare(strict_types=1);

namespace App\Order\Infrastructure\Doctrine;

use App\Order\Domain\Order;
use App\Order\Domain\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineOrderRepository implements OrderRepository
{
	public function __construct(
		private EntityManagerInterface $entityManager,
	) {
	}

	public function add(Order $order): void
	{
		$this->entityManager->persist($order);
		$this->entityManager->flush();
	}
}
