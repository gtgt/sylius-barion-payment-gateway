<?php

namespace GoncziAkos\SyliusBarionPaymentGateway;

use GoncziAkos\SyliusBarionPaymentGateway\Action\CaptureAction;
use GoncziAkos\SyliusBarionPaymentGateway\Action\NotifyAction;
use GoncziAkos\SyliusBarionPaymentGateway\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class SyliusPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'barion_payment',
            'payum.factory_title' => 'Barion Payment',
            'payum.action.capture' => new CaptureAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.status' => new StatusAction(),
        ]);

        $config['payum.api'] = function (ArrayObject $config) {
            return new SyliusApi($config['pos_key'], $config['payee'], $config['env']);
        };
    }
}
