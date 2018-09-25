<?php


namespace DigipolisGent\Domainator9k\SockBundle\Provider;

use DigipolisGent\SettingBundle\Provider\DataTypeProviderInterface;

/**
 * Class DataTypeProvider
 * @package DigipolisGent\Domainator9k\SockBundle\Provider
 */
class DataTypeProvider implements DataTypeProviderInterface
{

    /**
     * @return array
     */
    public function getDataTypes()
    {

        return [
            [
                'key' => 'sock_aliases',
                'label' => 'Sock URL aliases',
                'required' => false,
                'field_type' => 'sock_aliases',
                'entity_types' => ['application_environment'],
            ],
            [
                'key' => 'sock_server_id',
                'label' => 'Sock server',
                'required' => true,
                'field_type' => 'sock_server',
                'entity_types' => ['server'],
            ],
            [
                'key' => 'manage_sock',
                'label' => 'Manage sock',
                'required' => false,
                'field_type' => 'manage_sock',
                'entity_types' => ['server'],
            ],
            [
                'key' => 'sock_account_id',
                'label' => 'Sock account id',
                'required' => false,
                'field_type' => 'disabled_integer',
                'entity_types' => ['application_environment'],
                'order' => 1
            ],
            [
                'key' => 'sock_application_id',
                'label' => 'Sock application id',
                'required' => false,
                'field_type' => 'disabled_integer',
                'entity_types' => ['application_environment'],
                'order' => 2
            ],
            [
                'key' => 'sock_database_id',
                'label' => 'Sock database id',
                'required' => false,
                'field_type' => 'disabled_integer',
                'entity_types' => ['application_environment'],
            ],
            [
                'key' => 'sock_ssh_user',
                'label' => 'Sock ssh user',
                'required' => false,
                'field_type' => 'disabled_string',
                'entity_types' => ['application_environment'],
                'order' => 3
            ],
            [
                'key' => 'sock_ssh_key',
                'label' => 'Sock ssh keys',
                'required' => false,
                'field_type' => 'ssh_key_choice',
                'entity_types' => ['environment', 'application_environment'],
                'order' => 4
            ],
            [
                'key' => 'parent_application',
                'label' => 'Parent application',
                'required' => false,
                'field_type' => 'parent_application',
                'entity_types' => ['application'],
                'order' => 5
            ],
            [
                'key' => 'sock_application_technology',
                'label' => 'Sock application technology',
                'required' => true,
                'field_type' => 'sock_technology',
                'entity_types' => ['application'],
                'order' => 6,
                'default_value' => 'php-fpm',
            ],
            [
                'key' => 'database_host',
                'label' => 'Database host',
                'required' => true,
                'field_type' => 'database_host',
                'entity_types' => ['environment','application_environment'],
            ],
        ];
    }
}
