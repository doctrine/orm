<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;

use function iterator_to_array;

/** @covers \Doctrine\ORM\Internal\Hydration\AbstractHydrator */
class AbstractHydratorTest extends OrmFunctionalTestCase
{
    /** @var EventManager&MockObject */
    private $mockEventManager;

    /** @var Result&MockObject */
    private $mockResult;

    /** @var ResultSetMapping&MockObject */
    private $mockResultMapping;

    /** @var AbstractHydrator&MockObject */
    private $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $mockConnection             = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $this->mockEventManager     = $this->createMock(EventManager::class);
        $this->mockResult           = $this->createMock(Result::class);
        $this->mockResultMapping    = $this->createMock(ResultSetMapping::class);

        $mockEntityManagerInterface
            ->expects(self::any())
            ->method('getEventManager')
            ->willReturn($this->mockEventManager);
        $mockEntityManagerInterface
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($mockConnection);
        $this->mockResult
            ->expects(self::any())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->hydrator = $this
            ->getMockBuilder(AbstractHydrator::class)
            ->setConstructorArgs([$mockEntityManagerInterface])
            ->getMockForAbstractClass();
    }

    /**
     * @group DDC-3146
     * @group #1515
     *
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the
     * number of removed events
     */
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

        iterator_to_array($this->hydrator->iterate($this->mockResult, $this->mockResultMapping));
    }

    /** @group #6623 */
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

    /** @group #8482 */
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
            ->willThrowException(new ORMException());

        $this->expectException(ORMException::class);
        $this->hydrator->hydrateAll($this->mockResult, $this->mockResultMapping);
    }
}
