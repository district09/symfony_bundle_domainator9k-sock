<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\SettingBundle\FieldType\StringFieldType;
use DigipolisGent\SettingBundle\Service\DataValueService;

/**
 * Class ManageSockFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class DatabaseHostFieldType extends StringFieldType
{

    private $dataValueService;

    /**
     * ManageSockFieldType constructor.
     * @param DataValueService $dataValueService
     */
    public function __construct(DataValueService $dataValueService)
    {
        $this->dataValueService = $dataValueService;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'database_host';
    }

    /**
     * @param $value
     * @return array
     */
    public function getOptions($value): array
    {
        $options = parent::getOptions($value);

        $defaultValue = '';

        if ($this->originEntity instanceof ApplicationEnvironment) {
            $defaultValue = $this->dataValueService->getValue($this->originEntity->getEnvironment(), self::getName());
        }

        $options['attr']['value'] = $value ? $value : $defaultValue;

        return $options;
    }
}
