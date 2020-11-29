<?php

declare(strict_types=1);

namespace GoncziAkos\SyliusBarionPaymentGateway\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mcc_sylius_barion_payment_gateway_plugin');
        $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
