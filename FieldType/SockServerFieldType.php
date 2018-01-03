<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;


use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SockServerFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SockServerFieldType extends AbstractFieldType
{

    private $apiService;

    public function __construct(ApiService $apiService)
    {
        $this->apiService = $apiService;
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

        $virtualServers = $this->apiService->getVirtualServers();
        foreach ($virtualServers as $virtualServer) {
            $options['choices'][$virtualServer['hostname']] = $virtualServer['id'];
        }

        $options['data'] = json_decode($value, true);

        return $options;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'socker_server';
    }
}