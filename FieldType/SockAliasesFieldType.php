<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Regex;

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

        $options['entry_type'] = TextType::class;
        $options['allow_add'] = true;
        $options['allow_delete'] = true;
        $options['delete_empty'] = true;
        $options['data'] = $this->decodeValue($value);
        $options['entry_options']['constraints'] = new Regex(
            [
                'pattern' => '/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$/',
                'message' => 'The domain is not valid',
            ]
        );
        $options['entry_options']['attr']['pattern'] = '^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*\.[a-z]{2,63}$';

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
        return $value ? array_values(json_decode($value, true)) : [];
    }

    public function encodeValue($value): ?string
    {
        return json_encode($value ? array_values($value) : []);
    }
}
