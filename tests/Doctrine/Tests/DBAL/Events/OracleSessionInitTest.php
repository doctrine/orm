<?php

namespace Doctrine\Tests\DBAL\Events;

use Doctrine\Tests\DbalTestCase;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

require_once __DIR__ . '/../../TestInit.php';

class OracleSessionInitTest extends DbalTestCase
{
    public function testPostConnect()
    {
        $connectionMock = $this->getMock('Doctrine\DBAL\Connection', array(), array(), '', false);
        $connectionMock->expects($this->once())
                       ->method('executeUpdate')
                       ->with($this->isType('string'));

        $eventArgs = new ConnectionEventArgs($connectionMock);


        $listener = new OracleSessionInit();
        $listener->postConnect($eventArgs);
    }

    public function testGetSubscribedEvents()
    {
        $listener = new OracleSessionInit();
        $this->assertEquals(array(Events::postConnect), $listener->getSubscribedEvents());
    }
}