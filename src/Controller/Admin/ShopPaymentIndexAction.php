<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Controller\Admin;

use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Provider\GridProviderInterface;
use Sylius\Component\Grid\View\GridViewFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final class ShopPaymentIndexAction
{
    private const GRID_ID = 'sylius_barion_plugin_shop_payment';

    public function __construct(
        private readonly GridProviderInterface $gridProvider,
        private readonly GridViewFactoryInterface $gridViewFactory,
        private readonly Environment $twig,
    ) {
    }

    #[Route(
        '/barion/transactions/shop',
        name: 'sylius_barion_admin_transaction_shop_index',
        methods: ['GET'],
        defaults: ['_sylius' => ['permission' => true]],
    )]
    public function __invoke(Request $request): Response
    {
        $grid = $this->gridProvider->get(self::GRID_ID);
        $resources = $this->gridViewFactory->create($grid, new Parameters($request->query->all()));

        return new Response($this->twig->render('@SyliusBarionPaymentGatewayPlugin/admin/transaction/shop/index.html.twig', [
            'resources' => $resources,
        ]));
    }
}
