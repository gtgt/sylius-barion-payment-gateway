<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Controller\Admin;

use SyliusBarionPaymentGateway\Factory\BarionApiFactory;
use SyliusBarionPaymentGateway\Provider\BarionPaymentMethodProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class WalletHistoryIndexAction
{
    public function __construct(
        private readonly BarionPaymentMethodProvider $barionPaymentMethodProvider,
        private readonly BarionApiFactory $barionApiFactory,
        private readonly Environment $twig,
    ) {
    }

    #[Route(
        '/barion/transactions/wallet',
        name: 'sylius_barion_admin_transaction_wallet_index',
        methods: ['GET'],
        defaults: ['_sylius' => ['permission' => true]],
    )]
    public function __invoke(Request $request): Response
    {
        $paymentMethods = $this->barionPaymentMethodProvider->getBarionPaymentMethods();
        $selectedPaymentMethodId = $request->query->getInt('paymentMethodId') ?: null;
        $selectedPaymentMethod = $this->barionPaymentMethodProvider->getBarionPaymentMethod($selectedPaymentMethodId);

        if (null === $selectedPaymentMethod && [] !== $paymentMethods) {
            $selectedPaymentMethod = $paymentMethods[0];
        }

        $currency = $request->query->getString('currency') ?: null;
        if ('' === $currency) {
            $currency = null;
        }

        $lastVisibleItemId = $request->query->getString('lastVisibleItemId') ?: null;
        if ('' === $lastVisibleItemId) {
            $lastVisibleItemId = null;
        }

        $limit = max(1, min(100, $request->query->getInt('limit', 20)));

        $historyResponse = null;
        $apiError = null;

        if (null !== $selectedPaymentMethod) {
            try {
                $walletApi = $this->barionApiFactory->createWalletApiFromPaymentMethod($selectedPaymentMethod);
                $historyResponse = $walletApi->getUserHistory($limit, $currency, $lastVisibleItemId);
            } catch (\Throwable $exception) {
                $apiError = $exception->getMessage();
            }
        }

        return new Response($this->twig->render('@SyliusBarionPaymentGatewayPlugin/admin/transaction/wallet/index.html.twig', [
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'historyResponse' => $historyResponse,
            'apiError' => $apiError,
            'currency' => $currency,
            'limit' => $limit,
            'lastVisibleItemId' => $lastVisibleItemId,
        ]));
    }
}
