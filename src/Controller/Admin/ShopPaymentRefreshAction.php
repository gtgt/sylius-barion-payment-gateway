<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Controller\Admin;

use Payum\Core\Bridge\Spl\ArrayObject;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use SyliusBarionPaymentGateway\Action\BarionStatusMapper;
use SyliusBarionPaymentGateway\Factory\BarionApiFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShopPaymentRefreshAction
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly BarionApiFactory $barionApiFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(
        '/barion/transactions/shop/{id}/refresh',
        name: 'sylius_barion_admin_transaction_shop_refresh',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
        defaults: ['_sylius' => ['permission' => true]],
    )]
    public function __invoke(Request $request, int $id): Response
    {
        $payment = $this->paymentRepository->find($id);
        if (!$payment instanceof PaymentInterface) {
            throw new NotFoundHttpException(sprintf('Payment with id "%d" does not exist.', $id));
        }

        if ($payment->getMethod()?->getGatewayConfig()?->getFactoryName() !== 'barion_payment') {
            throw new NotFoundHttpException('Payment is not handled by the Barion gateway.');
        }

        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $barionPaymentId = $details['paymentId'] ?? null;

        if (!is_string($barionPaymentId) || '' === $barionPaymentId) {
            $this->addFlash($request, 'error', 'sylius_barion.flash.missing_barion_payment_id');

            return new RedirectResponse($this->urlGenerator->generate('sylius_barion_admin_transaction_shop_show', ['id' => $id]));
        }

        try {
            $api = $this->barionApiFactory->createSyliusApiFromPaymentMethod($payment->getMethod());
            $paymentState = $api->getPaymentState($barionPaymentId);

            if ($paymentState->RequestSuccessful) {
                BarionStatusMapper::applyPaymentState($details, $paymentState);
                $payment->setDetails($details->getArrayCopy());
                $this->paymentRepository->add($payment);
                $this->addFlash($request, 'success', 'sylius_barion.flash.payment_refreshed');
            } else {
                $this->addFlash($request, 'error', 'sylius_barion.flash.payment_refresh_failed');
            }
        } catch (\Throwable) {
            $this->addFlash($request, 'error', 'sylius_barion.flash.payment_refresh_failed');
        }

        return new RedirectResponse($this->urlGenerator->generate('sylius_barion_admin_transaction_shop_show', ['id' => $id]));
    }

    private function addFlash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
