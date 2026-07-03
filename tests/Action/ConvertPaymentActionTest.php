<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Action;

use Payum\Core\Model\Payment as PayumPayment;
use Payum\Core\Request\Convert;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\Payment;
use SyliusBarionPaymentGateway\Action\ConvertPaymentAction;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class ConvertPaymentActionTest extends TestCase
{
    public function test_it_converts_sylius_payment_to_initial_barion_details(): void
    {
        $payment = new Payment();
        $action = new ConvertPaymentAction();
        $request = new Convert($payment, 'array');

        $action->execute($request);

        self::assertSame(['status' => BarionPaymentStatus::NEW], $request->getResult());
    }

    public function test_it_preserves_existing_sylius_payment_details(): void
    {
        $payment = new Payment();
        $payment->setDetails(['paymentId' => 'barion-123', 'status' => BarionPaymentStatus::PENDING]);
        $action = new ConvertPaymentAction();
        $request = new Convert($payment, 'array');

        $action->execute($request);

        self::assertSame([
            'paymentId' => 'barion-123',
            'status' => BarionPaymentStatus::PENDING,
        ], $request->getResult());
    }

    public function test_it_converts_payum_payment_model(): void
    {
        $payment = new PayumPayment();
        $action = new ConvertPaymentAction();
        $request = new Convert($payment, 'array');

        $action->execute($request);

        self::assertSame(['status' => BarionPaymentStatus::NEW], $request->getResult());
    }
}
