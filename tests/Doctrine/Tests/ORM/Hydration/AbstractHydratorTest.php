<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\Events;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @covers \Doctrine\ORM\Internal\Hydration\AbstractHydrator
 */
class AbstractHydratorTest extends OrmFunctionalTestCase
{
    /**
     * @group DDC-3146
     * @group #1515
     *
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the
     * number of removed events
     */
    public function testOnClearEventListenerIsDetachedOnCleanup()
    {
        $mockConnection             = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $mockEventManager           = $this->createMock(EventManager::class);
        $mockStatement              = $this->createMock(Statement::class);
        $mockUow                    = $this->createMock(UnitOfWork::class);
        $mockMetadataFactory        = $this->createMock(ClassMetadataFactory::class);
        $mockResultMapping          = $this->getMockBuilder(ResultSetMapping::class);

        $mockEntityManagerInterface->method('getEventManager')->willReturn($mockEventManager);
        $mockEntityManagerInterface->method('getConnection')->willReturn($mockConnection);
        $mockEntityManagerInterface->method('getUnitOfWork')->willReturn($mockUow);
        $mockEntityManagerInterface->method('getMetadataFactory')->willReturn($mockMetadataFactory);
        $mockStatement->expects(self::once())->method('fetch')->willReturn(false);

        /* @var $mockAbstractHydrator AbstractHydrator */
        $mockAbstractHydrator = $this
            ->getMockBuilder(AbstractHydrator::class)
            ->setConstructorArgs([$mockEntityManagerInterface])
            ->setMethods(['hydrateAllData'])
            ->getMock();

        $mockEventManager
            ->expects(self::at(0))
            ->method('addEventListener')
            ->with([Events::onClear], $mockAbstractHydrator);

        $mockEventManager
            ->expects(self::at(1))
            ->method('removeEventListener')
            ->with([Events::onClear], $mockAbstractHydrator);

        iterator_to_array($mockAbstractHydrator->iterate($mockStatement, $mockResultMapping));
    }
}
