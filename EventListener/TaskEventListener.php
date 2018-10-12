<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;

use DigipolisGent\Domainator9k\CoreBundle\Entity\Task;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockAccountProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockApplicationProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\BuildSockDatabaseProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\DestroySockAccountProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\DestroySockApplicationProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\DestroySockDatabaseProvisioner;
use DigipolisGent\Domainator9k\SockBundle\Provisioner\PollingProvisioner;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Class TaskEventListener
 * @package DigipolisGent\Domainator9k\SockBundle\EventListener
 */
class TaskEventListener
{

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Task) {
            $provisioners = $entity->getProvisioners();
            $needsPolling  = [
                BuildSockAccountProvisioner::class,
                BuildSockApplicationProvisioner::class,
                BuildSockDatabaseProvisioner::class,
                DestroySockAccountProvisioner::class,
                DestroySockApplicationProvisioner::class,
                DestroySockDatabaseProvisioner::class,
            ];
            if ($provisioners && array_intersect($provisioners, $needsPolling) && !in_array(PollingProvisioner::class, $provisioners)) {
                $provisioners[] = PollingProvisioner::class;
                $entity->setProvisioners($provisioners);
            }
        }
    }
}
