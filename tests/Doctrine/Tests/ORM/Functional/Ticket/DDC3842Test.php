<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC3842Test extends OrmFunctionalTestCase
{
    /**
     * Array of spl object hashes to compare
     * @type array
     */
    protected $platformHashes = array();

    /**
     * The setUp method creates an entityManager and detects the DatabasePlatform
     * BEFORE I can hook to the postConnect event. Even if I close and reconnect the
     * db connection, the DatabasePlatform will not be detected again, so I cannot
     * test this and reuse OrmFunctionalTestCase::setUp in any other way.
     */
    protected function _getEntityManager($config = null, $eventManager = null)
    {
        $em = parent::_getEntityManager($config, $eventManager);

        $eventManager = $em->getEventManager();
        if (! $eventManager->hasListeners(Events::postConnect)) {
            $eventManager->addEventListener(Events::postConnect, $this);
        }

        return $em;
    }

    public function testDatabasePlatformIsNotOverriden()
    {
        $conn = $this->_em->getConnection();
        $conn->close();

        // Use the "getDatabasePlatform" method to trigger a $conn->connect()
        // Connection needs to connect in VersionAwarePlatformDrivers to get
        // the PlatformVersion
        $platform = $conn->getDatabasePlatform();
        $this->platformHashes[] = spl_object_hash($platform);

        $this->assertCount(2, $this->platformHashes,
            "postConnect event was not fired. The test depends on this event.");

        $this->assertEquals($this->platformHashes[0], $this->platformHashes[1],
            "DatabasePlatform objects differ between the postConnect listener and the EntityManager connection object.");
    }

    /**
     * postConnect event listener.
     * In here, we'll get the first DatabasePlatform object.
     * This DatabasePlatform object is getting overriden by a new one after the
     * call to $conn->getDatabasePlatform() resolves.
     *
     * @param \Doctrine\DBAL\Event\ConnectionEventArgs $args
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $platform = $args->getDatabasePlatform();

        $this->platformHashes[] = spl_object_hash($platform);
    }
}
