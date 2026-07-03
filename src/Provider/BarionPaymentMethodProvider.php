<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Provider;

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;

final class BarionPaymentMethodProvider
{
    public function __construct(
        private readonly PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    /**
     * @return list<PaymentMethodInterface>
     */
    public function getBarionPaymentMethods(): array
    {
        /** @var list<PaymentMethodInterface> $methods */
        $methods = $this->paymentMethodRepository->createQueryBuilder('paymentMethod')
            ->innerJoin('paymentMethod.gatewayConfig', 'gatewayConfig')
            ->andWhere('gatewayConfig.factoryName = :factoryName')
            ->setParameter('factoryName', 'barion_payment')
            ->orderBy('paymentMethod.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $methods;
    }

    public function getBarionPaymentMethod(?int $paymentMethodId): ?PaymentMethodInterface
    {
        if (null === $paymentMethodId) {
            $methods = $this->getBarionPaymentMethods();

            return $methods[0] ?? null;
        }

        foreach ($this->getBarionPaymentMethods() as $paymentMethod) {
            if ($paymentMethod->getId() === $paymentMethodId) {
                return $paymentMethod;
            }
        }

        return null;
    }
}
