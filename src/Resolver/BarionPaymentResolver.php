<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Resolver;

use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use SyliusBarionPaymentGateway\Doctrine\ORM\PaymentRepository;

final class BarionPaymentResolver
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function findByBarionPaymentId(string $barionPaymentId): ?PaymentInterface
    {
        if (!$this->paymentRepository instanceof PaymentRepository) {
            return null;
        }

        return $this->paymentRepository->findOneByBarionPaymentId($barionPaymentId);
    }
}
