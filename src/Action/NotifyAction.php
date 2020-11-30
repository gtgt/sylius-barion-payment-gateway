<?php


namespace GoncziAkos\SyliusBarionPaymentGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Payum\Core\Request\GetHumanStatus;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait, SyliusApiTrait;

    /**
     * {@inheritdoc}
     *
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $details = $payment->getDetails();

        if ($details['status'] === GetHumanStatus::STATUS_PENDING) {
            $response = $this->api->getPaymentState($details['paymentId']);
            if ($response->RequestSuccessful && 'Succeeded' == $response->Status) {
                $details['status'] = GetHumanStatus::STATUS_CAPTURED;
                $payment->setDetails($details);
                throw new HttpResponse('', 200);
            }
        }
        throw new HttpResponse('', 403);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}
