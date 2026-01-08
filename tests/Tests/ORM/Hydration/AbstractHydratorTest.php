<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\Hydration\SimpleEntity;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;

use function iterator_to_array;

/** @covers \Doctrine\ORM\Internal\Hydration\AbstractHydrator */
class AbstractHydratorTest extends OrmFunctionalTestCase
{
    /** @var EventManager */
    private $eventManager;

    /** @var Result&MockObject */
    private $mockResult;

    /** @var ResultSetMapping&MockObject */
    private $mockResultMapping;

    /** @var DummyHydrator */
    private $hydrator;

    protected function setUp(): void
    {
        parent::setUp();

        $mockConnection             = $this->createMock(Connection::class);
        $mockEntityManagerInterface = $this->createMock(EntityManagerInterface::class);
        $this->eventManager         = new EventManager();
        $this->mockResult           = $this->createMock(Result::class);
        $this->mockResultMapping    = $this->createMock(ResultSetMapping::class);

        $mockEntityManagerInterface
            ->expects(self::any())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $mockEntityManagerInterface
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($mockConnection);
        $this->mockResult
            ->expects(self::any())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->hydrator = new DummyHydrator($mockEntityManagerInterface);
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
        $iterator = $this->hydrator->iterate($this->mockResult, $this->mockResultMapping);
        self::assertTrue($this->eventManager->hasListeners(Events::onClear));
        iterator_to_array($iterator);
        self::assertFalse($this->eventManager->hasListeners(Events::onClear));
    }

    /** @group #6623 */
    public function testHydrateAllRegistersAndClearsAllAttachedListeners(): void
    {
        $this->hydrator->hydrateAll($this->mockResult, $this->mockResultMapping);
        self::assertTrue($this->hydrator->hasListener);
        self::assertFalse($this->eventManager->hasListeners(Events::onClear));
    }

    /** @group #8482 */
    public function testHydrateAllClearsAllAttachedListenersEvenOnError(): void
    {
        $this->hydrator->throwException = true;

        $this->expectException(ORMException::class);
        $this->hydrator->hydrateAll($this->mockResult, $this->mockResultMapping);
        self::assertTrue($this->hydrator->hasListener);
        self::assertFalse($this->eventManager->hasListeners(Events::onClear));
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

class DummyHydrator extends AbstractHydrator
{
    /** @var bool */
    public $throwException = false;

    /** @var bool */
    public $hasListener = false;

    /** {@inheritDoc} */
    protected function hydrateAllData()
    {
        if ($this->throwException) {
            throw NotSupported::create();
        }

        return [];
    }

    public function prepare(): void
    {
        $this->hasListener = $this->_em->getEventManager()->hasListeners(Events::onClear);
    }
}
