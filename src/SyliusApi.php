<?php


namespace GoncziAkos\SyliusBarionPaymentGateway;

use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class SyliusApi
{
    /** @var string */
    private $posKey;

    /** @var string */
    private $payee;

    /** @var string */
    private $env;

    public function __construct(string $posKey, string $payee, string $env)
    {
        $this->posKey = $posKey;
        $this->payee = $payee;
        $this->env = $env;
    }

    public function getPosKey(): string
    {
        return $this->posKey;
    }

    public function getPayee(): string
    {
        return $this->payee;
    }

    public function getEnv(): string
    {
        return $this->env;
    }

    private function getBarionClient()
    {
        return new \BarionClient($this->posKey, 2, $this->env);
    }

    public function preparePayment(SyliusPaymentInterface $payment, float $total, string $redirectUrl, string $callbackUrl)
    {
        $transaction = new \PaymentTransactionModel();
        $transaction->POSTransactionId = $payment->getId();
        $transaction->Payee = $this->payee;
        $transaction->Total = $total;

        $paymentRequest = new \PreparePaymentRequestModel();
        $paymentRequest->OrderNumber = $payment->getId();
        $paymentRequest->PaymentRequestId = $payment->getId() . '-' . date('y-m-d_h:i:s');
        $paymentRequest->PayerHint = $payment->getOrder()->getCustomer()->getEmail();
        $paymentRequest->Locale = str_replace('_', '-', $payment->getOrder()->getLocaleCode());
        $paymentRequest->Currency = $payment->getOrder()->getCurrencyCode();

        $shipmentAddress = $payment->getOrder()->getShippingAddress();
        if ($shipmentAddress) {
            $paymentRequest->ShippingAddress =
                $shipmentAddress->getPostcode()
                . ' ' . $shipmentAddress->getCity()
                . ' ' . $shipmentAddress->getStreet();
        }

        $paymentRequest->RedirectUrl = $redirectUrl;
        $paymentRequest->CallbackUrl = $callbackUrl;

        $paymentRequest->AddTransaction($transaction);

        return $this->getBarionClient()->PreparePayment($paymentRequest);
    }

    public function getPaymentState(string $paymentId)
    {
        return $this->getBarionClient()->GetPaymentState($paymentId);

    }
}
