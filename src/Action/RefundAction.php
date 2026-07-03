<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Request\Refund;
use Sylius\Component\Core\Model\PaymentInterface;

final class RefundAction extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param Refund $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $payment = $request->getFirstModel();

        if (!$payment instanceof PaymentInterface || empty($details['paymentId']) || empty($details['transactions'])) {
            return;
        }

        $currency = new GetCurrency($payment->getCurrencyCode());
        $this->gateway->execute($currency);
        $amount = $payment->getAmount() / (10 ** $currency->exp);

        $transaction = $details['transactions'][0] ?? null;
        if (!is_array($transaction) || empty($transaction['transactionId']) || empty($transaction['posTransactionId'])) {
            return;
        }

        $response = $this->api->refundPayment(
            (string) $details['paymentId'],
            (string) $transaction['transactionId'],
            (string) $transaction['posTransactionId'],
            $amount,
        );

        if ($response->RequestSuccessful) {
            $state = $this->api->getPaymentState((string) $details['paymentId']);
            if ($state->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $state);
                $payment->setDetails($details->getArrayCopy());
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Refund && $request->getModel() instanceof \ArrayAccess;
    }
}
