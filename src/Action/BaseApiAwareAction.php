<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use SyliusBarionPaymentGateway\SyliusApi;

abstract class BaseApiAwareAction implements ActionInterface, ApiAwareInterface
{
    protected SyliusApi $api;

    public function setApi($api): void
    {
        if (!$api instanceof SyliusApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }

        $this->api = $api;
    }
}
