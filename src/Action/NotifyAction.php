<?php


namespace Goncziakos\SyliusBarionPaymentGateway\Action;


use Goncziakos\SyliusBarionPaymentGateway\SyliusApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Payum\Core\Request\GetHumanStatus;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /** @var SyliusApi */
    private $api;

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
        $status = new GetHumanStatus($request->getModel());
        $this->gateway->execute($status);
        $details = ArrayObject::ensureArrayObject($payment);

        if ($status->isPending()) {
            $response = $this->api->getPaymentState($details['paymentId']);
            if ($response->RequestSuccessful && 'Succeeded' == $response->Status) {
                $details['status'] = GetHumanStatus::STATUS_CAPTURED;
                $status->markCaptured();
                throw new HttpResponse('', 200);
            }
        }
        throw new HttpResponse('', 403);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess;
    }

    public function setApi($api): void
    {
        if (!$api instanceof SyliusApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . SyliusApi::class);
        }

        $this->api = $api;
    }
}
