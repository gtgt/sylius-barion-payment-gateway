<?php


namespace GoncziAkos\SyliusBarionPaymentGateway\Action;

use GoncziAkos\SyliusBarionPaymentGateway\SyliusApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\Request\Capture;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait, GenericTokenFactoryAwareTrait;

    /** @var SyliusApi */
    private $api;

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment);

        $status = new GetHumanStatus($request->getModel());
        $this->gateway->execute($status);

        if ($status->isNew()) {
            try {
                $notifyToken = $this->tokenFactory->createNotifyToken(
                    $token->getGatewayName(),
                    $token->getDetails()
                );
                $details['notifyToken'] = $notifyToken->getHash();
                $details['notifyURL'] = $notifyToken->getTargetUrl();
                $response = $this->api->preparePayment(
                    $payment,
                    $request->getToken()->getTargetUrl(),
                    $details['notifyURL']
                );
            } catch (\Exception $exception) {
                $details['status'] = GetHumanStatus::STATUS_FAILED;
            }
            if (isset($response) && $response->RequestSuccessful && 'Prepared' == $response->Status && $response->PaymentId) {
                $status->markPending();
                $details['status'] = GetHumanStatus::STATUS_PENDING;
                $details['paymentId'] = urldecode($response->PaymentId);
                $details['paymentUrl'] = urldecode($response->PaymentRedirectUrl);
                throw new HttpRedirect($details['paymentUrl']);
            }
            $details['status'] = GetHumanStatus::STATUS_FAILED;
            if (isset($response)) {
                $details['errors'] = $response->Errors;
            }
            $status->markFailed();
        } elseif ($status->isPending()) {
            $response = $this->api->getPaymentState($details['paymentId']);
            if ($response->RequestSuccessful && 'Succeeded' == $response->Status) {
                $details['status'] = GetHumanStatus::STATUS_CAPTURED;
                $status->markCaptured();
            }
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface;
    }

    public function setApi($api): void
    {
        if (!$api instanceof SyliusApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }

        $this->api = $api;
    }
}
