<?php

declare(strict_types=1);

namespace SyliusBarionPaymentGateway\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

class AdminMenuListener
{
    public function buildMenu(MenuBuilderEvent $menuBuilderEvent): void
    {
        $menu = $menuBuilderEvent->getMenu();
        $salesMenu = $menu->getChild('sales');

        if (null === $salesMenu) {
            return;
        }

        $salesMenu
            ->addChild('barion_transactions', [
                'route' => 'sylius_barion_admin_transaction_shop_index',
            ])
            ->setLabel('sylius_barion.ui.barion_transactions')
            ->setLabelAttribute('icon', 'tabler:credit-card')
        ;
    }
}
