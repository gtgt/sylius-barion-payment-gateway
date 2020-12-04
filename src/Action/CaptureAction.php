<?php


namespace GoncziAkos\SyliusBarionPaymentGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetCurrency;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\Request\Capture;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use GatewayAwareTrait, GenericTokenFactoryAwareTrait, SyliusApiTrait;

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();
        $details = $payment->getDetails();

        if (empty($details['status'])) {
            try {
                $notifyToken = $this->tokenFactory->createNotifyToken(
                    $token->getGatewayName(),
                    $token->getDetails()
                );
                $details['notifyToken'] = $notifyToken->getHash();
                $details['notifyURL'] = $notifyToken->getTargetUrl();

                $currency = new GetCurrency($payment->getCurrencyCode());
                $this->gateway->execute($currency);
                $divisor = pow(10, $currency->exp);

                $response = $this->api->preparePayment(
                    $payment,
                    $payment->getAmount() / $divisor,
                    $request->getToken()->getTargetUrl(),
                    $details['notifyURL']
                );
            } catch (\Exception $exception) {
                $details['status'] = GetHumanStatus::STATUS_FAILED;
            }
            if (isset($response) && $response->RequestSuccessful && 'Prepared' == $response->Status && $response->PaymentId) {
                $details['status'] = GetHumanStatus::STATUS_PENDING;
                $details['paymentId'] = urldecode($response->PaymentId);
                $details['paymentUrl'] = urldecode($response->PaymentRedirectUrl);
                $payment->setDetails($details);
                throw new HttpRedirect($details['paymentUrl']);
            }
            $details['status'] = GetHumanStatus::STATUS_FAILED;
            if (isset($response)) {
                $details['errors'] = $response->Errors;
            }
        } elseif ($details['status'] === GetHumanStatus::STATUS_PENDING) {
            $response = $this->api->getPaymentState($details['paymentId']);
            if ($response->RequestSuccessful && 'Succeeded' == $response->Status) {
                $details['status'] = GetHumanStatus::STATUS_CAPTURED;
            }
        }
        $payment->setDetails($details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}
