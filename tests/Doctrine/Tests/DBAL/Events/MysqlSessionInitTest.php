<?php

namespace Doctrine\Tests\DBAL\Events;

use Doctrine\Tests\DbalTestCase;
use Doctrine\DBAL\Event\Listeners\MysqlSessionInit;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

require_once __DIR__ . '/../../TestInit.php';

class MysqlSessionInitTest extends DbalTestCase
{
    public function testPostConnect()
    {
        $connectionMock = $this->getMock('Doctrine\DBAL\Connection', array(), array(), '', false);
        $connectionMock->expects($this->once())
                       ->method('executeUpdate')
                       ->with($this->equalTo("SET NAMES foo COLLATE bar"));

        $eventArgs = new ConnectionEventArgs($connectionMock);


        $listener = new MysqlSessionInit('foo', 'bar');
        $listener->postConnect($eventArgs);
    }

    public function testGetSubscribedEvents()
    {
        $listener = new MysqlSessionInit();
        $this->assertEquals(array(Events::postConnect), $listener->getSubscribedEvents());
    }
}