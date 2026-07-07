<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Cancel;
use Sylius\Component\Core\Model\PaymentInterface;

class CancelAuthorizationAction extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param Cancel $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $payment = $request->getFirstModel();

        if (!$payment instanceof PaymentInterface || empty($details['paymentId'])) {
            return;
        }

        $response = $this->api->cancelAuthorization($details['paymentId']);

        if ($response->RequestSuccessful) {
            $state = $this->api->getPaymentState($details['paymentId']);
            if ($state->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $state);
                $payment->setDetails($details->getArrayCopy());
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Cancel && $request->getModel() instanceof \ArrayAccess;
    }
}
