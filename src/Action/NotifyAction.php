<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Symfony\Component\HttpFoundation\Response;

final class NotifyAction extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $payment = $request->getFirstModel();
        if (!$payment instanceof PaymentInterface) {
            throw new HttpResponse(null, Response::HTTP_FORBIDDEN);
        }

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($details['paymentId'])) {
            throw new HttpResponse(null, Response::HTTP_FORBIDDEN);
        }

        try {
            $response = $this->api->getPaymentState((string) $details['paymentId']);

            if ($response->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $response);
                $payment->setDetails($details->getArrayCopy());
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Barion IPN processing failed.', [
                'payment_id' => $payment->getId(),
                'barion_payment_id' => $details['paymentId'] ?? null,
                'exception' => $exception,
            ]);

            throw new HttpResponse(null, Response::HTTP_FORBIDDEN);
        }

        throw new HttpResponse(null, Response::HTTP_OK);
    }

    public function supports($request): bool
    {
        return $request instanceof Notify && $request->getModel() instanceof \ArrayAccess;
    }
}
