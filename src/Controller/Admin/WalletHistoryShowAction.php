<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Controller\Admin;

use SyliusBarionPaymentGateway\Factory\BarionApiFactory;
use SyliusBarionPaymentGateway\Provider\BarionPaymentMethodProvider;
use SyliusBarionPaymentGateway\Resolver\BarionPaymentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class WalletHistoryShowAction
{
    public function __construct(
        private readonly BarionPaymentMethodProvider $barionPaymentMethodProvider,
        private readonly BarionApiFactory $barionApiFactory,
        private readonly BarionPaymentResolver $barionPaymentResolver,
        private readonly Environment $twig,
    ) {
    }

    #[Route(
        '/barion/transactions/wallet/{historyItemId}',
        name: 'sylius_barion_admin_transaction_wallet_show',
        methods: ['GET'],
        defaults: ['_sylius' => ['permission' => true]],
    )]
    public function __invoke(Request $request, string $historyItemId): Response
    {
        $selectedPaymentMethod = $this->barionPaymentMethodProvider->getBarionPaymentMethod(
            $request->query->getInt('paymentMethodId') ?: null,
        );

        if (null === $selectedPaymentMethod) {
            throw new NotFoundHttpException('No Barion payment method is configured.');
        }

        $detailResponse = null;
        $apiError = null;
        $linkedPayment = null;

        try {
            $walletApi = $this->barionApiFactory->createWalletApiFromPaymentMethod($selectedPaymentMethod);
            $detailResponse = $walletApi->getTransactionDetail($historyItemId);

            $barionPaymentId = $detailResponse->resolveBarionPaymentId();
            if (null !== $barionPaymentId) {
                $linkedPayment = $this->barionPaymentResolver->findByBarionPaymentId($barionPaymentId);
            }
        } catch (\Throwable $exception) {
            $apiError = $exception->getMessage();
        }

        return new Response($this->twig->render('@SyliusBarionPaymentGatewayPlugin/admin/transaction/wallet/show.html.twig', [
            'historyItemId' => $historyItemId,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'detailResponse' => $detailResponse,
            'linkedPayment' => $linkedPayment,
            'apiError' => $apiError,
        ]));
    }
}
