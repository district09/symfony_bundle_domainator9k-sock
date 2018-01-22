<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\AbstractApplication;
use DigipolisGent\SettingBundle\FieldType\AbstractFieldType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Class ParentApplicationFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class ParentApplicationFieldType extends AbstractFieldType
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        $applications = $this->entityManager->getRepository(AbstractApplication::class)->findAll();
        foreach ($applications as $application) {
            if ($this->getOriginEntity()->getId() != $application->getId()) {
                $options['choices'][$application->getName()] = $application->getId();
            }
        }

        $options['data'] = json_decode($value, true);

        return $options;
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        return 'parent_application';
    }

    /**
     * @param $value
     * @return string
     */
    public function encodeValue($value): string
    {
        return $value;
    }

    /**
     * @param $value
     * @return null|AbstractApplication
     */
    public function decodeValue($value)
    {
        return $this->entityManager->getRepository(AbstractApplication::class)->find($value);
    }
}
