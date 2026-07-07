<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use Sylius\Bundle\CoreBundle\Doctrine\ORM\PaymentRepository as BasePaymentRepository;
use Sylius\Component\Core\Model\PaymentInterface;

class PaymentRepository extends BasePaymentRepository
{
    public function createBarionTransactionsQueryBuilder(): QueryBuilder
    {
        return $this->createListQueryBuilder()
            ->innerJoin('o.method', 'barionPaymentMethod')
            ->innerJoin('barionPaymentMethod.gatewayConfig', 'barionGatewayConfig')
            ->andWhere('barionGatewayConfig.factoryName = :barionFactory')
            ->setParameter('barionFactory', 'barion_payment')
        ;
    }

    public function findOneByBarionPaymentId(string $barionPaymentId): ?PaymentInterface
    {
        /** @var PaymentInterface|null $payment */
        $payment = $this->createQueryBuilder('payment')
            ->innerJoin('payment.method', 'barionPaymentMethod')
            ->innerJoin('barionPaymentMethod.gatewayConfig', 'barionGatewayConfig')
            ->andWhere('barionGatewayConfig.factoryName = :barionFactory')
            ->andWhere('payment.details LIKE :paymentId')
            ->setParameter('barionFactory', 'barion_payment')
            ->setParameter('paymentId', '%' . $this->jsonEncodedValuePattern($barionPaymentId) . '%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $payment;
    }

    private function jsonEncodedValuePattern(string $value): string
    {
        return json_encode($value, \JSON_THROW_ON_ERROR);
    }
}
