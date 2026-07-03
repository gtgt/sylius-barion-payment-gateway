<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Action;

use Barion\Enumerations\PaymentStatus;
use PHPUnit\Framework\TestCase;
use SyliusBarionPaymentGateway\Action\BarionStatusMapper;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class BarionStatusMapperTest extends TestCase
{
    public function test_it_maps_barion_payment_statuses_to_detail_statuses(): void
    {
        self::assertSame(BarionPaymentStatus::PENDING, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Prepared));
        self::assertSame(BarionPaymentStatus::CAPTURED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Succeeded));
        self::assertSame(BarionPaymentStatus::AUTHORIZED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Authorized));
        self::assertSame(BarionPaymentStatus::FAILED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Failed));
        self::assertSame(BarionPaymentStatus::CANCELED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Canceled));
        self::assertSame(BarionPaymentStatus::EXPIRED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Expired));
        self::assertSame(BarionPaymentStatus::RESERVED, BarionStatusMapper::mapPaymentStatus(PaymentStatus::Reserved));
    }
}
