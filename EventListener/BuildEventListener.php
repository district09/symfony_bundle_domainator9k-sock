<?php


namespace DigipolisGent\Domainator9k\SockBundle\EventListener;


use DigipolisGent\Domainator9k\CoreBundle\Event\BuildEvent;

/**
 * Class BuildEventListener
 * @package DigipolisGent\Domainator9k\SockBundle\EventListener
 */
class BuildEventListener
{

    /**
     * @param BuildEvent $event
     */
    public function onBuild(BuildEvent $event)
    {
        $applicationEnvironment = $event->getBuild()->getApplicationEnvironment();
    }

}