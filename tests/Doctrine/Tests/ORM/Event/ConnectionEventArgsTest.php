<?php namespace Doctrine\Tests\ORM\Event;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\TestUtil;

class ConnectionEventArgsTest extends OrmFunctionalTestCase
{
    protected $platformHashes = array();
    protected $eventWasAdded = false;
    protected $eventWasFired = false;

    protected function _getEntityManager($config = null, $eventManager = null)
    {
        $em = parent::_getEntityManager($config, $eventManager);

        $eventManager = $em->getEventManager();
        if (! $eventManager->hasListeners(Events::postConnect))
        {
            $eventManager->addEventListener(Events::postConnect, $this);
        }

        return $em;
    }

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testDatabasePlatformIsNotOverriden()
    {
        $conn = $this->_em->getConnection();
        $conn->close();

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        $this->assertTrue($this->eventWasFired);

        $this->platformHashes[] = spl_object_hash($platform);
        $this->assertCount(2, $this->platformHashes);
        $this->assertEquals($this->platformHashes[0], $this->platformHashes[1]);
    }

    public function postConnect(ConnectionEventArgs $args)
    {
        $platform = $args->getDatabasePlatform();

        $this->platformHashes[] = spl_object_hash($platform);
        $this->eventWasFired = true;
    }
}
