<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class SyliusPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'barion_payment',
            'payum.factory_title' => 'Barion Payment',
            'env' => 'test',
        ]);

        $config['payum.api'] = static function (ArrayObject $config): SyliusApi {
            return new SyliusApi($config->getArrayCopy());
        };
    }
}
