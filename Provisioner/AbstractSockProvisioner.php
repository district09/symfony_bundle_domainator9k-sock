<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Entity\ApplicationEnvironment;
use DigipolisGent\Domainator9k\CoreBundle\Entity\VirtualServer;
use DigipolisGent\Domainator9k\CoreBundle\Exception\LoggedException;
use DigipolisGent\Domainator9k\CoreBundle\Provisioner\AbstractProvisioner;
use DigipolisGent\Domainator9k\CoreBundle\Service\TaskLoggerService;
use DigipolisGent\Domainator9k\SockBundle\Service\ApiService;
use DigipolisGent\SettingBundle\Service\DataValueService;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class AbstractSockProvisioner
 *
 * @package DigipolisGent\Domainator9k\SockBundle\Provisioner
 */
abstract class AbstractSockProvisioner extends AbstractProvisioner
{
    protected function ensurePollingProvisioner()
    {
        $provisioners = $this->task->getProvisioners();
        if ($provisioners && !in_array(PollingProvisioner::class, $provisioners)) {
          $provisioners[] = PollingProvisioner::class;
          $this->task->setProvisioners($provisioners);
        }
    }
}
