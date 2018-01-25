<?php


namespace DigipolisGent\Domainator9k\SockBundle\DependencyInjection;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class DigipolisGentDomainator9kSockExtension
 * @package DigipolisGent\Domainator9k\SockBundle\DependencyInjection
 */
class DigipolisGentDomainator9kSockExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $definition = $container->getDefinition(ApiService::class);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition->addMethodCall('setHost', [$config['host']]);
        $definition->addMethodCall('setClientToken', [$config['client_token']]);
        $definition->addMethodCall('setUserToken', [$config['user_token']]);
    }
}
