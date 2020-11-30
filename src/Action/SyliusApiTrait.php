<?php

namespace GoncziAkos\SyliusBarionPaymentGateway\Action;

use GoncziAkos\SyliusBarionPaymentGateway\SyliusApi;
use Payum\Core\Exception\UnsupportedApiException;

trait SyliusApiTrait
{
    /**
     * @var SyliusApi
     */
    private $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api): void
    {
        if (!$api instanceof SyliusApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }

        $this->api = $api;
    }
}
