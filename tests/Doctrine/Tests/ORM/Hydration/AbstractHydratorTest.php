<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @covers \Doctrine\ORM\Internal\Hydration\AbstractHydrator
 */
class AbstractHydratorTest extends OrmFunctionalTestCase
{
    /**
     * @var EventManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockEventManager;

    /**
     * @var Statement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockStatement;

    /**
     * @var ResultSetMapping|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResultMapping;

    /**
     * @var AbstractHydrator
     */
    private $hydrator;

    protected function setUp() : void
    {
        parent::setUp();

        $mockConnection             = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $mockUow                    = $this->createMock(UnitOfWork::class);
        $mockMetadataFactory       = $this->createMock(ClassMetadataFactory::class);

        $this->mockEventManager     = $this->createMock(EventManager::class);
        $this->mockStatement        = $this->createMock(Statement::class);
        $this->mockResultMapping    = $this->getMockBuilder(ResultSetMapping::class);

        $mockEntityManagerInterface
            ->method('getEventManager')
            ->willReturn($this->mockEventManager);

        $mockEntityManagerInterface
            ->method('getConnection')
            ->willReturn($mockConnection);

        $mockEntityManagerInterface
            ->method('getUnitOfWork')
            ->willReturn($mockUow);

        $mockEntityManagerInterface
            ->method('getMetadataFactory')
            ->willReturn($mockMetadataFactory);

        $this->mockStatement
            ->expects(self::once())
            ->method('fetch')
            ->willReturn(false);

        $this->hydrator = $this
            ->getMockBuilder(AbstractHydrator::class)
            ->setConstructorArgs([$mockEntityManagerInterface])
            ->setMethods(['hydrateAllData'])
            ->getMock();
    }

    /**
     * @group DDC-3146
     * @group #1515
     *
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the
     * number of removed events
     */
    public function testOnClearEventListenerIsDetachedOnCleanup() : void
    {
        $this
            ->mockEventManager
            ->expects(self::at(0))
            ->method('addEventListener')
            ->with([Events::onClear], $this->hydrator);

        $this
            ->mockEventManager
            ->expects(self::at(1))
            ->method('removeEventListener')
            ->with([Events::onClear], $this->hydrator);

        iterator_to_array($this->hydrator->iterate($this->mockStatement, $this->mockResultMapping));
    }

    /**
     * @group #6623
     */
    public function testHydrateAllRegistersAndClearsAllAttachedListeners() : void
    {
        $this
            ->mockEventManager
            ->expects(self::at(0))
            ->method('addEventListener')
            ->with([Events::onClear], $this->hydrator);

        $this
            ->mockEventManager
            ->expects(self::at(1))
            ->method('removeEventListener')
            ->with([Events::onClear], $this->hydrator);

        $this->hydrator->hydrateAll($this->mockStatement, $this->mockResultMapping);
    }
}
