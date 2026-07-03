<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\PayumBundle\Request\GetStatus;
use Sylius\Component\Core\Model\PaymentInterface;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class StatusAction extends BaseApiAwareAction implements GatewayAwareInterface
{
    use GatewayAwareTrait;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param GetStatus $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $payment = $request->getFirstModel();
        if (!$payment instanceof PaymentInterface) {
            $request->markUnknown();

            return;
        }

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        if (!empty($details['paymentId']) && $this->shouldPollRemoteState((string) ($details['status'] ?? ''))) {
            $this->pollRemoteState($details, $payment);
        }

        $this->markRequestFromDetails($request, (string) ($details['status'] ?? BarionPaymentStatus::NEW));
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatus && $request->getFirstModel() instanceof PaymentInterface;
    }

    private function shouldPollRemoteState(string $status): bool
    {
        return in_array($status, [
            BarionPaymentStatus::PENDING,
            BarionPaymentStatus::AUTHORIZED,
            BarionPaymentStatus::RESERVED,
            BarionPaymentStatus::PARTIAL,
            BarionPaymentStatus::UNKNOWN,
        ], true);
    }

    private function pollRemoteState(ArrayObject $details, PaymentInterface $payment): void
    {
        try {
            $response = $this->api->getPaymentState((string) $details['paymentId']);

            if ($response->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $response);
                $payment->setDetails($details->getArrayCopy());
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Barion status polling failed.', [
                'payment_id' => $payment->getId(),
                'barion_payment_id' => $details['paymentId'] ?? null,
                'exception' => $exception,
            ]);
        }
    }

    private function markRequestFromDetails(GetStatus $request, string $status): void
    {
        match ($status) {
            BarionPaymentStatus::NEW => $request->markNew(),
            BarionPaymentStatus::PENDING, BarionPaymentStatus::RESERVED => $request->markPending(),
            BarionPaymentStatus::AUTHORIZED => $request->markAuthorized(),
            BarionPaymentStatus::CAPTURED => $request->markCaptured(),
            BarionPaymentStatus::FAILED => $request->markFailed(),
            BarionPaymentStatus::CANCELED => $request->markCanceled(),
            BarionPaymentStatus::EXPIRED => $request->markExpired(),
            default => $request->markUnknown(),
        };
    }
}
