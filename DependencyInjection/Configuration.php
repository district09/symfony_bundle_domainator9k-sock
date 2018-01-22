<?php


namespace DigipolisGent\Domainator9k\SockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package DigipolisGent\Domainator9k\SockBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('domainator_sock');
        $rootNode
            ->children()
            ->scalarNode('host')->cannotBeEmpty()->end()
            ->scalarNode('user_token')->cannotBeEmpty()->end()
            ->scalarNode('client_token')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
