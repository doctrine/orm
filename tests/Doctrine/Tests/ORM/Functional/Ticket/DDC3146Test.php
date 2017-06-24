<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-3146
 * @author Emiel Nijpels <emiel@silverstreet.com>
 */
class DDC3146Test extends OrmFunctionalTestCase
{
    /**
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the number of removed events
     */
    public function testEventListeners()
    {
        $mockConnection = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $mockEventManager = $this->createMock(EventManager::class);
        $mockStatement = $this->createMock(Statement::class);
        $mockResultMapping = $this->getMockBuilder(ResultSetMapping::class);

        $mockEntityManagerInterface->expects(self::any())->method('getEventManager')->willReturn($mockEventManager);
        $mockEntityManagerInterface->expects(self::any())->method('getConnection')->willReturn($mockConnection);
        $mockStatement->expects(self::once())->method('fetch')->willReturn(false);

        $mockAbstractHydrator = $this->getMockBuilder(AbstractHydrator::class)
            ->setConstructorArgs(array($mockEntityManagerInterface))
            ->setMethods(['hydrateAllData'])
            ->getMock();

        // Increase counter every time the event listener is added and decrease the counter every time the event listener is removed
        $eventCounter = 0;
        $mockEventManager->expects(self::atLeastOnce())
            ->method('addEventListener')
            ->willReturnCallback(function () use (&$eventCounter) {
                $eventCounter++;
            });

        $mockEventManager->expects(self::atLeastOnce())
            ->method('removeEventListener')
            ->willReturnCallback(function () use (&$eventCounter) {
                $eventCounter--;
            });

        // Create iterable result
        $iterableResult = $mockAbstractHydrator->iterate($mockStatement, $mockResultMapping, array());
        $iterableResult->next();

        // Number of added events listeners should be equal or less than the number of removed events
        self::assertSame(0, $eventCounter, 'More events added to the event listener than removed; this can create a memory leak when references are not cleaned up');
    }
}
