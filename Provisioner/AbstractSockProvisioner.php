<?php

namespace DigipolisGent\Domainator9k\SockBundle\Provisioner;

use DigipolisGent\Domainator9k\CoreBundle\Provisioner\AbstractProvisioner;

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
