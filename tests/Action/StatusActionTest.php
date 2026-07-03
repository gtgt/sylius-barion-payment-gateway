<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Action;

use Sylius\Bundle\PayumBundle\Request\GetStatus;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sylius\Component\Core\Model\PaymentInterface;
use SyliusBarionPaymentGateway\Action\StatusAction;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class StatusActionTest extends TestCase
{
    public function test_it_marks_captured_status_from_payment_details(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getDetails')->willReturn([
            'status' => BarionPaymentStatus::CAPTURED,
        ]);

        $request = new GetStatus($payment);
        $action = new StatusAction(new NullLogger());
        $action->execute($request);

        self::assertTrue($request->isCaptured());
    }

    public function test_it_marks_authorized_status_from_payment_details(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getDetails')->willReturn([
            'status' => BarionPaymentStatus::AUTHORIZED,
        ]);

        $request = new GetStatus($payment);
        $action = new StatusAction(new NullLogger());
        $action->execute($request);

        self::assertTrue($request->isAuthorized());
    }

    public function test_it_supports_sylius_get_status_with_payment_model(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $request = new GetStatus($payment);
        $action = new StatusAction(new NullLogger());

        self::assertTrue($action->supports($request));
    }

    public function test_it_does_not_support_non_get_status_requests(): void
    {
        $action = new StatusAction(new NullLogger());

        self::assertFalse($action->supports(new \stdClass()));
    }
}
