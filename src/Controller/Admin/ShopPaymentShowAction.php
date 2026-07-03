<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Controller\Admin;

use Payum\Core\Bridge\Spl\ArrayObject;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use SyliusBarionPaymentGateway\Action\BarionStatusMapper;
use SyliusBarionPaymentGateway\Factory\BarionApiFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class ShopPaymentShowAction
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BarionApiFactory $barionApiFactory,
        private readonly Environment $twig,
    ) {
    }

    #[Route(
        '/barion/transactions/shop/{id}',
        name: 'sylius_barion_admin_transaction_shop_show',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
        defaults: ['_sylius' => ['permission' => true]],
    )]
    public function __invoke(Request $request, int $id): Response
    {
        $payment = $this->getBarionPayment($id);
        $paymentState = null;
        $apiError = null;

        $details = $payment->getDetails();
        $barionPaymentId = $details['paymentId'] ?? null;

        if (is_string($barionPaymentId) && '' !== $barionPaymentId) {
            try {
                $api = $this->barionApiFactory->createSyliusApiFromPaymentMethod($payment->getMethod());
                $paymentState = $api->getPaymentState($barionPaymentId);

                if ($paymentState->RequestSuccessful) {
                    $detailsObject = ArrayObject::ensureArrayObject($details);
                    BarionStatusMapper::applyPaymentState($detailsObject, $paymentState);
                    $payment->setDetails($detailsObject->getArrayCopy());
                    $this->paymentRepository->add($payment);
                }
            } catch (\Throwable $exception) {
                $apiError = $exception->getMessage();
            }
        }

        return new Response($this->twig->render('@SyliusBarionPaymentGatewayPlugin/admin/transaction/shop/show.html.twig', [
            'payment' => $payment,
            'paymentState' => $paymentState,
            'apiError' => $apiError,
        ]));
    }

    private function getBarionPayment(int $id): PaymentInterface
    {
        $payment = $this->paymentRepository->find($id);
        if (!$payment instanceof PaymentInterface) {
            throw new NotFoundHttpException(sprintf('Payment with id "%d" does not exist.', $id));
        }

        if ($payment->getMethod()?->getGatewayConfig()?->getFactoryName() !== 'barion_payment') {
            throw new NotFoundHttpException('Payment is not handled by the Barion gateway.');
        }

        return $payment;
    }
}
