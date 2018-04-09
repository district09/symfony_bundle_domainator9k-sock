<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\CiTypes\JenkinsBundle\Entity\GroovyScript;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SshKeyChoiceFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SshKeyChoiceFieldType extends AbstractFieldType
{

    private $apiService;
    private $dataValueService;

    /**
     * SshKeyChoiceFieldType constructor.
     * @param ApiService $apiService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ApiService $apiService, DataValueService $dataValueService)
    {
        $this->apiService = $apiService;
        $this->dataValueService = $dataValueService;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'ssh_key_choice';
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return ChoiceType::class;
    }

    /**
     * @param $value
     * @return array
     */
    public function getOptions($value): array
    {
        $options = [];

        $options['multiple'] = true;
        $options['expanded'] = true;

        $sshKeys = $this->apiService->getSshKeys();
        foreach ($sshKeys as $sshKey) {
            $options['choices'][$sshKey['description']] = $sshKey['id'];
        }

        $options['data'] = json_decode($value, true);

        $originEntity = $this->getOriginEntity();

        if ($originEntity instanceof ApplicationEnvironment && is_null($originEntity->getId())) {
            $options['data'] = $this->dataValueService->getValue($originEntity->getEnvironment(), 'sock_ssh_key');
        }

        return $options;
    }

    /**
     * @param $value
     * @return string
     */
    public function encodeValue($value): ?string
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function decodeValue($value)
    {
        $decodedValue = json_decode($value, true);
        if (!is_array($decodedValue)) {
            return [];
        }

        return $decodedValue;
    }
}
