<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Doctrine\ORM;

use PHPUnit\Framework\TestCase;
use SyliusBarionPaymentGateway\Doctrine\ORM\PaymentRepository;

final class PaymentRepositoryTest extends TestCase
{
    public function test_it_extends_sylius_payment_repository(): void
    {
        self::assertTrue(method_exists(PaymentRepository::class, 'createBarionTransactionsQueryBuilder'));
        self::assertTrue(method_exists(PaymentRepository::class, 'findOneByBarionPaymentId'));
        self::assertTrue(is_subclass_of(PaymentRepository::class, \Sylius\Bundle\CoreBundle\Doctrine\ORM\PaymentRepository::class));
    }
}
