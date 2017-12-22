<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\CiTypes\JenkinsBundle\Entity\GroovyScript;
use DigipolisGent\Domainator9k\CiTypes\JenkinsBundle\Entity\JenkinsJob;
use DigipolisGent\Domainator9k\CoreBundle\Entity\AbstractApplication;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationType;
use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationTypeEnvironment;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Entity\SettingDataValue;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use DigipolisGent\SettingBundle\FieldType\FieldTypeInterface;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SshKeyChoiceFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SshKeyChoiceFieldType extends AbstractFieldType
{

    private $apiService;
    private $entityManager;

    public function __construct(ApiService $apiService,EntityManagerInterface $entityManager)
    {
        $this->apiService = $apiService;
        $this->entityManager = $entityManager;
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
        foreach ($sshKeys as $sshKey){
            $options['choices'][$sshKey['description']] = $sshKey['id'];
        }

        $options['data'] = json_decode($value, true);

        $originEntity = $this->getOriginEntity();

        if ($originEntity instanceof ApplicationEnvironment && is_null($originEntity->getId())) {
            $settingDataValue = $this->entityManager->getRepository(SettingDataValue::class)
                ->findOneByKey($originEntity->getEnvironment(), 'sock_ssh_key');

            if ($settingDataValue) {
                $options['data'] = json_decode($settingDataValue->getValue(), true);
            }
        }

        return $options;
    }

    /**
     * @param $value
     * @return string
     */
    public function encodeValue($value): string
    {
        return json_encode($value);
    }

    public function decodeValue($value)
    {
        return json_decode($value,true);
    }
}