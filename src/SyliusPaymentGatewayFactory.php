<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class SyliusPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'barion_payment',
            'payum.factory_title' => 'Barion Payment',
            'env' => 'test',
            'payum.translator' => '@translator',
        ]);

        $config['payum.api'] = function (ArrayObject $config): SyliusApi {
            $translator = $config['payum.translator'];
            if (!$translator instanceof TranslatorInterface) {
                throw new \InvalidArgumentException(sprintf(
                    'Expected an instance of %s.',
                    TranslatorInterface::class,
                ));
            }

            return new SyliusApi($config->getArrayCopy(), $translator);
        };
    }
}
