<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;

/**
 * Class SockAliasesFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SockAliasesFieldType extends AbstractFieldType
{

    public function getFormType(): string
    {
        return CollectionType::class;
    }

    public function getOptions($value): array
    {
        $options = [];

        $options['entry_type'] = UrlType::class;
        $options['allow_add'] = true;
        $options['allow_delete'] = true;
        $options['delete_empty'] = true;
        $options['data'] = $this->decodeValue($value);

        return $options;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'sock_aliases';
    }

    public function decodeValue($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function encodeValue($value): ?string
    {
        return json_encode($value ? $value : []);
    }
}
