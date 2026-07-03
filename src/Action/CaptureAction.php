<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Action;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use SyliusBarionPaymentGateway\Model\BarionPaymentStatus;

final class CaptureAction extends BaseApiAwareAction implements GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait;

    private ?GenericTokenFactoryInterface $tokenFactory = null;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function setGenericTokenFactory(?GenericTokenFactoryInterface $genericTokenFactory = null): void
    {
        $this->tokenFactory = $genericTokenFactory;
    }

    /**
     * @param Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $payment = $request->getFirstModel();

        if (!$payment instanceof PaymentInterface) {
            throw new \LogicException('Capture request must contain a payment model.');
        }

        $status = (string) ($details['status'] ?? BarionPaymentStatus::NEW);

        if (BarionPaymentStatus::AUTHORIZED === $status) {
            $this->captureAuthorizedPayment($details, $payment);

            return;
        }

        if (BarionPaymentStatus::RESERVED === $status) {
            $this->finishReservedPayment($details, $payment);

            return;
        }

        if (BarionPaymentStatus::NEW === $status || '' === $status) {
            $this->startPayment($request, $details, $payment);

            return;
        }

        if (BarionPaymentStatus::PENDING === $status && !empty($details['paymentId'])) {
            $this->pollPaymentState($details, $payment);
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Capture && $request->getModel() instanceof \ArrayAccess;
    }

    /**
     * @param Capture $request
     */
    private function startPayment(Capture $request, ArrayObject $details, PaymentInterface $payment): void
    {
        /** @var TokenInterface $token */
        $token = $request->getToken();

        if (null === $this->tokenFactory) {
            throw new \RuntimeException('Generic token factory is not configured.');
        }

        try {
            $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $token->getDetails());
            $details['notifyToken'] = $notifyToken->getHash();
            $details['notifyURL'] = $notifyToken->getTargetUrl();

            $currency = new GetCurrency($payment->getCurrencyCode());
            $this->gateway->execute($currency);

            $divisor = 10 ** $currency->exp;
            $response = $this->api->preparePayment(
                $payment,
                $payment->getAmount() / $divisor,
                $token->getTargetUrl(),
                (string) $details['notifyURL'],
            );

            BarionStatusMapper::applyPrepareResponse($details, $response);
            $this->syncDetails($details, $payment);

            if (!empty($details['paymentUrl'])) {
                throw new HttpRedirect((string) $details['paymentUrl']);
            }

            $details['status'] = BarionPaymentStatus::FAILED;
            $this->syncDetails($details, $payment);
        } catch (HttpRedirect $redirect) {
            throw $redirect;
        } catch (\Throwable $exception) {
            $this->logger->error('Barion payment preparation failed.', [
                'payment_id' => $payment->getId(),
                'exception' => $exception,
            ]);

            $details['status'] = BarionPaymentStatus::FAILED;
            $details['error'] = $exception->getMessage();
            $this->syncDetails($details, $payment);
        }
    }

    private function pollPaymentState(ArrayObject $details, PaymentInterface $payment): void
    {
        try {
            $response = $this->api->getPaymentState((string) $details['paymentId']);

            if ($response->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $response);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('Barion payment state polling failed.', [
                'payment_id' => $payment->getId(),
                'barion_payment_id' => $details['paymentId'] ?? null,
                'exception' => $exception,
            ]);
        }

        $this->syncDetails($details, $payment);
    }

    private function captureAuthorizedPayment(ArrayObject $details, PaymentInterface $payment): void
    {
        if (empty($details['paymentId']) || empty($details['transactions'])) {
            return;
        }

        $transactions = $this->buildCaptureTransactions($details, $payment);
        $response = $this->api->captureAuthorizedPayment((string) $details['paymentId'], $transactions);

        if ($response->RequestSuccessful) {
            $this->pollPaymentState($details, $payment);

            return;
        }

        $details['status'] = BarionPaymentStatus::FAILED;
        $details['error'] = 'Barion capture request failed.';
        $this->syncDetails($details, $payment);
    }

    private function finishReservedPayment(ArrayObject $details, PaymentInterface $payment): void
    {
        if (empty($details['paymentId']) || empty($details['transactions'])) {
            return;
        }

        $transactions = $this->buildCaptureTransactions($details, $payment);
        $response = $this->api->finishReservation((string) $details['paymentId'], $transactions);

        if ($response->RequestSuccessful) {
            $this->pollPaymentState($details, $payment);

            return;
        }

        $details['status'] = BarionPaymentStatus::FAILED;
        $details['error'] = 'Barion finish reservation request failed.';
        $this->syncDetails($details, $payment);
    }

    private function buildCaptureTransactions(ArrayObject $details, PaymentInterface $payment): array
    {
        $currency = new GetCurrency($payment->getCurrencyCode());
        $this->gateway->execute($currency);
        $total = $payment->getAmount() / (10 ** $currency->exp);

        $transactions = [];
        foreach ($details['transactions'] as $transaction) {
            if (!is_array($transaction) || empty($transaction['transactionId'])) {
                continue;
            }

            $transactions[] = [
                'transactionId' => $transaction['transactionId'],
                'total' => $total,
            ];
        }

        return $transactions;
    }

    private function syncDetails(ArrayObject $details, PaymentInterface $payment): void
    {
        $payment->setDetails($details->getArrayCopy());
    }
}
