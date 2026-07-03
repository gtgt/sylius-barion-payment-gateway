<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Model\Wallet;

use PHPUnit\Framework\TestCase;
use SyliusBarionPaymentGateway\Model\Wallet\GetTransactionDetailResponse;
use SyliusBarionPaymentGateway\Model\Wallet\GetUserHistoryResponse;
use SyliusBarionPaymentGateway\Model\Wallet\UserHistoryItem;

final class WalletModelTest extends TestCase
{
    public function test_it_parses_user_history_response(): void
    {
        $response = GetUserHistoryResponse::fromArray([
            'RequestSuccessful' => true,
            'UserHistory' => [
                [
                    'Id' => '11111111-1111-1111-1111-111111111111',
                    'Type' => 'CardPayment',
                    'HappenedAtUtc' => '2026-07-03T10:00:00Z',
                    'Amount' => 1500.0,
                    'Currency' => 'HUF',
                    'Description' => 'Order payment',
                    'IsInProgress' => false,
                    'SourceAccount' => ['Name' => 'Buyer', 'Email' => 'buyer@example.com'],
                    'TargetAccount' => ['Name' => 'Shop', 'Email' => 'shop@example.com'],
                ],
            ],
        ]);

        self::assertTrue($response->requestSuccessful);
        self::assertCount(1, $response->items);
        self::assertInstanceOf(UserHistoryItem::class, $response->items[0]);
        self::assertSame('CardPayment', $response->items[0]->type);
        self::assertSame('HUF', $response->items[0]->currency);
    }

    public function test_it_parses_transaction_detail_response_and_resolves_payment_id(): void
    {
        $response = GetTransactionDetailResponse::fromArray([
            'RequestSuccessful' => true,
            'DetailInformation' => [
                'PaymentId' => 'barion-payment-id',
                'Amount' => 42.0,
            ],
        ]);

        self::assertTrue($response->requestSuccessful);
        self::assertSame('barion-payment-id', $response->resolveBarionPaymentId());
    }
}
