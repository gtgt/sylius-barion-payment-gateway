<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Provider;

use Sylius\Bundle\PaymentBundle\Attribute\AsNotifyPaymentProvider;
use Sylius\Bundle\PaymentBundle\Provider\NotifyPaymentProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use SyliusBarionPaymentGateway\Resolver\BarionPaymentResolver;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

#[AsNotifyPaymentProvider(priority: 0)]
class BarionNotifyPaymentProvider implements NotifyPaymentProviderInterface
{
    public function __construct(
        private readonly BarionPaymentResolver $barionPaymentResolver,
    ) {
    }

    public function getPayment(Request $request, PaymentMethodInterface $paymentMethod): PaymentInterface
    {
        $barionPaymentId = $this->resolveBarionPaymentId($request);
        Assert::notNull($barionPaymentId, 'Barion payment id is missing from the callback request.');

        $payment = $this->barionPaymentResolver->findByBarionPaymentId($barionPaymentId);

        Assert::isInstanceOf($payment, PaymentInterface::class, sprintf('Payment not found for Barion id "%s".', $barionPaymentId));

        return $payment;
    }

    public function supports(Request $request, PaymentMethodInterface $paymentMethod): bool
    {
        if ($paymentMethod->getGatewayConfig()?->getFactoryName() !== 'barion_payment') {
            return false;
        }

        return null !== $this->resolveBarionPaymentId($request);
    }

    private function resolveBarionPaymentId(Request $request): ?string
    {
        $paymentId = $request->query->get('paymentId') ?? $request->request->get('paymentId');

        return is_string($paymentId) && '' !== $paymentId ? $paymentId : null;
    }
}
