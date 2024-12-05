<?php

namespace DigipolisGent\Domainator9k\SockBundle;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class DigipolisGentDomainator9kSockBundle extends AbstractBundle
{

    #[\Override]
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('host')->cannotBeEmpty()->end()
            ->scalarNode('user_token')->cannotBeEmpty()->end()
            ->scalarNode('client_token')->cannotBeEmpty()->end()
            ->end();
    }

    #[\Override]
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yml');

        $container->services()
            ->get(ApiService::class)
            ->call('setHost', [$config['host']])
            ->call('setClientToken', [$config['client_token']])
            ->call('setUserToken', [$config['user_token']])
        ;
    }
}
