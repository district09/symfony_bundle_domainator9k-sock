<?php


namespace DigipolisGent\Domainator9k\SockBundle\FieldType;

use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\SettingBundle\FieldType\BooleanFieldType;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class ManageSockFieldType
 * @package DigipolisGent\Domainator9k\SockBundle\FieldType
 */
class ManageSockFieldType extends BooleanFieldType
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
        return 'manage_sock';
    }

    /**
     * @param $value
     * @return array
     */
    public function getOptions($value): array
    {
        $options = parent::getOptions($value);
        $options['constraints'] = [
            new Callback(function ($value, ExecutionContextInterface $context) {
                if (!$value) {
                    return;
                }

                $currentServer = $this->getOriginEntity();

                $serverRepository = $this->entityManager->getRepository(VirtualServer::class);
                $servers = $serverRepository->findBy(['environment' => $currentServer->getEnvironment()]);

                foreach ($servers as $server) {
                    if ($server == $currentServer) {
                        continue;
                    }

                    $manageSock = $this->dataValueService->getValue($server, 'manage_sock');
                    if ($manageSock) {
                        $context->addViolation(
                            sprintf(
                                'The server with name %s is allready managing sock for the %s environment',
                                $server->getName(),
                                $server->getEnvironment()
                            )
                        );
                    }
                }
            })
        ];

        return $options;
    }
}
