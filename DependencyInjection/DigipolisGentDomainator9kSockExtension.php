<?php

namespace DigipolisGent\Domainator9k\SockBundle\DependencyInjection;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DigipolisGentDomainator9kSockExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $def = $container->getDefinition(ApiService::class);
        $def->addMethodCall('setHost', [$config['host']]);
        $def->addMethodCall('setClientToken', [$config['client_token']]);
        $def->addMethodCall('setUserToken', [$config['user_token']]);
    }
}
