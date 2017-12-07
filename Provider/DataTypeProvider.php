<?php


namespace DigipolisGent\Domainator9k\SockBundle\Provider;


use DigipolisGent\SettingBundle\Provider\DataTypeProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


class DataTypeProvider implements DataTypeProviderInterface
{

    /**
     * @return array
     */
    public function getDataTypes()
    {
        return [
            [
                'key' => 'sock_id',
                'label' => 'Sock id',
                'required' => true,
                'field_type' => 'string',
                'entity_types' => ['server'],
            ],
            [
                'key' => 'manage_sock',
                'label' => 'Manage sock',
                'required' => true,
                'field_type' => 'string',
                'entity_types' => ['server'],
            ],
            [
                'key' => 'sock_account_id',
                'label' => 'Sock account id',
                'required' => true,
                'field_type' => 'string',
                'entity_types' => ['application_environment'],
            ],
            [
                'key' => 'sock_database_id',
                'label' => 'Sock database id',
                'required' => true,
                'field_type' => 'string',
                'entity_types' => ['application_environment'],
            ],
        ];
    }
}