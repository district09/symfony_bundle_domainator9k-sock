<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SockServerFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SockServerFieldType extends AbstractFieldType
{

    /**
     * @var ApiService
     */
    private $apiService;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * Cache lifetime (one week).
     * @var int
     */
    const CACHE_LIFETIME = 60 * 60 * 24 * 7;

    public function __construct(ApiService $apiService, CacheItemPoolInterface $cache)
    {
        $this->apiService = $apiService;
        $this->cache = $cache;
    }


    public function getFormType(): string
    {
        return ChoiceType::class;
    }

    public function getOptions($value): array
    {
        $options = [];

        $options['multiple'] = false;
        $options['expanded'] = false;

        $virtualServers = $this->getVirtualServers();
        foreach ($virtualServers as $virtualServer) {
            $options['choices'][$virtualServer['hostname']] = $virtualServer['id'];
        }

        $options['data'] = $value;

        return $options;
    }

    protected function getVirtualServers()
    {
        $virtualServers = $this->cache->getItem('sock.virtual_servers');
        if (!$virtualServers->isHit()) {
            $virtualServers->set($this->apiService->getVirtualServers());
            $virtualServers->expiresAfter(self::CACHE_LIFETIME);
            $this->cache->save($virtualServers);
        }
        return $virtualServers->get();
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'sock_server';
    }
}
