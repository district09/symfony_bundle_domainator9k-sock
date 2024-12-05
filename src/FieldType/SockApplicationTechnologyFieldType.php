<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class SockApplicationTechnologyFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class SockApplicationTechnologyFieldType extends AbstractFieldType
{

    private $entityManager;
    private $dataValueService;

    /**
     * ManageSockFieldType constructor.
     * @param EntityManagerInterface $entityManager
     * @param DataValueService $dataValueService
     */
    public function __construct(EntityManagerInterface $entityManager, DataValueService $dataValueService)
    {
        $this->entityManager = $entityManager;
        $this->dataValueService = $dataValueService;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'sock_technology';
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

        $options['multiple'] = false;
        $options['expanded'] = false;
        $options['choices'] = [
            'php-fpm' => 'php-fpm',
            // TODO: rack_env when rack is chosen.
            'rack' => 'rack',
        ];

        $options['data'] = $value;

        return $options;
    }

}
