<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\Hydration\SimpleEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

use function iterator_to_array;

#[CoversClass(AbstractHydrator::class)]
class AbstractHydratorTest extends OrmFunctionalTestCase
{
    private EventManager&MockObject $mockEventManager;
    private Result&MockObject $mockResult;
    private ResultSetMapping&MockObject $mockResultMapping;
    private AbstractHydrator&MockObject $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $mockConnection             = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $this->mockEventManager     = $this->createMock(EventManager::class);
        $this->mockResult           = $this->createMock(Result::class);
        $this->mockResultMapping    = $this->createMock(ResultSetMapping::class);

        $mockConnection
            ->method('getDatabasePlatform')
            ->willReturn($this->createMock(AbstractPlatform::class));
        $mockEntityManagerInterface
            ->method('getEventManager')
            ->willReturn($this->mockEventManager);
        $mockEntityManagerInterface
            ->method('getConnection')
            ->willReturn($mockConnection);
        $this->mockResult
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->hydrator = $this
            ->getMockBuilder(AbstractHydrator::class)
            ->setConstructorArgs([$mockEntityManagerInterface])
            ->getMockForAbstractClass();
    }

    /**
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the
     * number of removed events
     */
    #[Group('DDC-3146')]
    #[Group('#1515')]
    public function testOnClearEventListenerIsDetachedOnCleanup(): void
    {
        $eventListenerHasBeenRegistered = false;

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('addEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertFalse($eventListenerHasBeenRegistered);
                $eventListenerHasBeenRegistered = true;
            });

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('removeEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertTrue($eventListenerHasBeenRegistered);
            });

        iterator_to_array($this->hydrator->toIterable($this->mockResult, $this->mockResultMapping));
    }

    #[Group('#6623')]
    public function testHydrateAllRegistersAndClearsAllAttachedListeners(): void
    {
        $eventListenerHasBeenRegistered = false;

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('addEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertFalse($eventListenerHasBeenRegistered);
                $eventListenerHasBeenRegistered = true;
            });

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('removeEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertTrue($eventListenerHasBeenRegistered);
            });

        $this->hydrator->hydrateAll($this->mockResult, $this->mockResultMapping);
    }

    #[Group('#8482')]
    public function testHydrateAllClearsAllAttachedListenersEvenOnError(): void
    {
        $eventListenerHasBeenRegistered = false;

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('addEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertFalse($eventListenerHasBeenRegistered);
                $eventListenerHasBeenRegistered = true;
            });

        $this
            ->mockEventManager
            ->expects(self::once())
            ->method('removeEventListener')
            ->with([Events::onClear], $this->hydrator)
            ->willReturnCallback(function () use (&$eventListenerHasBeenRegistered): void {
                $this->assertTrue($eventListenerHasBeenRegistered);
            });

        $this
            ->hydrator
            ->expects(self::once())
            ->method('hydrateAllData')
            ->willThrowException($this->createStub(ORMException::class));

        $this->expectException(ORMException::class);
        $this->hydrator->hydrateAll($this->mockResult, $this->mockResultMapping);
    }

    public function testToIterableIfYieldAndBreakBeforeFinishAlwaysCleansUp(): void
    {
        $this->setUpEntitySchema([SimpleEntity::class]);

        $entity1 = new SimpleEntity();
        $this->_em->persist($entity1);
        $entity2 = new SimpleEntity();
        $this->_em->persist($entity2);

        $this->_em->flush();
        $this->_em->clear();

        $evm = $this->_em->getEventManager();

        $q = $this->_em->createQuery('SELECT e.id FROM ' . SimpleEntity::class . ' e');

        // select two entities, but do no iterate
        $q->toIterable();
        self::assertCount(0, $evm->getListeners(Events::onClear));

        // select two entities, but abort after first record
        foreach ($q->toIterable() as $result) {
            self::assertCount(1, $evm->getListeners(Events::onClear));
            break;
        }

        self::assertCount(0, $evm->getListeners(Events::onClear));
    }
}
