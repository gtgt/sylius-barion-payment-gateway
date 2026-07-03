<?php

declare(strict_types=1);

namespace Tests\SyliusBarionPaymentGateway\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use SyliusBarionPaymentGateway\Controller\Admin\ShopPaymentRefreshAction;
use SyliusBarionPaymentGateway\Factory\BarionApiFactory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ShopPaymentRefreshActionTest extends TestCase
{
    public function test_it_throws_when_payment_is_missing(): void
    {
        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('find')->with(10)->willReturn(null);

        $action = new ShopPaymentRefreshAction(
            $paymentRepository,
            new BarionApiFactory(HttpClient::create()),
            $this->createMock(UrlGeneratorInterface::class),
        );

        $this->expectException(NotFoundHttpException::class);

        $action(Request::create('/refresh', 'POST'), 10);
    }

    public function test_it_throws_when_payment_is_not_barion(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);

        $gatewayConfig->method('getFactoryName')->willReturn('offline');
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);
        $payment->method('getMethod')->willReturn($paymentMethod);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('find')->with(10)->willReturn($payment);

        $action = new ShopPaymentRefreshAction(
            $paymentRepository,
            new BarionApiFactory(HttpClient::create()),
            $this->createMock(UrlGeneratorInterface::class),
        );

        $this->expectException(NotFoundHttpException::class);

        $action(Request::create('/refresh', 'POST'), 10);
    }

    public function test_it_redirects_when_barion_payment_id_is_missing(): void
    {
        $payment = $this->createMock(PaymentInterface::class);
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $gatewayConfig = $this->createMock(GatewayConfigInterface::class);

        $gatewayConfig->method('getFactoryName')->willReturn('barion_payment');
        $paymentMethod->method('getGatewayConfig')->willReturn($gatewayConfig);
        $payment->method('getMethod')->willReturn($paymentMethod);
        $payment->method('getDetails')->willReturn([]);

        $paymentRepository = $this->createMock(PaymentRepositoryInterface::class);
        $paymentRepository->method('find')->with(10)->willReturn($payment);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')
            ->with('sylius_barion_admin_transaction_shop_show', ['id' => 10])
            ->willReturn('/admin/barion/transactions/shop/10')
        ;

        $action = new ShopPaymentRefreshAction(
            $paymentRepository,
            new BarionApiFactory(HttpClient::create()),
            $urlGenerator,
        );

        $request = Request::create('/refresh', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $action($request, 10);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/barion/transactions/shop/10', $response->getTargetUrl());
    }
}
