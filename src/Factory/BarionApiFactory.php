<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Factory;

use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use SyliusBarionPaymentGateway\Api\BarionWalletApi;
use SyliusBarionPaymentGateway\SyliusApi;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BarionApiFactory
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }
    public function createSyliusApiFromGatewayConfig(GatewayConfigInterface $gatewayConfig): SyliusApi
    {
        return new SyliusApi($gatewayConfig->getConfig());
    }

    public function createSyliusApiFromPaymentMethod(PaymentMethodInterface $paymentMethod): SyliusApi
    {
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if (null === $gatewayConfig) {
            throw new \InvalidArgumentException('Payment method has no gateway configuration.');
        }

        return $this->createSyliusApiFromGatewayConfig($gatewayConfig);
    }

    public function createWalletApiFromPaymentMethod(PaymentMethodInterface $paymentMethod): BarionWalletApi
    {
        $gatewayConfig = $paymentMethod->getGatewayConfig();
        if (null === $gatewayConfig) {
            throw new \InvalidArgumentException('Payment method has no gateway configuration.');
        }

        $config = $gatewayConfig->getConfig();

        return new BarionWalletApi(
            $this->httpClient,
            (string) ($config['pos_key'] ?? ''),
            (string) ($config['env'] ?? 'test'),
        );
    }
}
