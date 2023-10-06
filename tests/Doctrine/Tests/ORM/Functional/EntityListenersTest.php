<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\Company\CompanyPerson;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1955')]
class EntityListenersTest extends OrmFunctionalTestCase
{
    private CompanyContractListener $listener;

    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->listener = $this->_em->getConfiguration()
            ->getEntityListenerResolver()
            ->resolve(CompanyContractListener::class);
    }

    public function testPreFlushListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->preFlushCalls = [];

        $this->_em->persist($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->preFlushCalls);
        self::assertSame($fix, $this->listener->preFlushCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->preFlushCalls[0][0]);
        self::assertInstanceOf(PreFlushEventArgs::class, $this->listener->preFlushCalls[0][1]);
    }

    public function testPostLoadListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();
        $this->_em->clear();

        $this->listener->postLoadCalls = [];

        $dql = 'SELECT f FROM Doctrine\Tests\Models\Company\CompanyFixContract f WHERE f.id = ?1';
        $fix = $this->_em->createQuery($dql)->setParameter(1, $fix->getId())->getSingleResult();

        self::assertCount(1, $this->listener->postLoadCalls);
        self::assertSame($fix, $this->listener->postLoadCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->postLoadCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->postLoadCalls[0][1]);
    }

    public function testPrePersistListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->prePersistCalls = [];

        $this->_em->persist($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->prePersistCalls);
        self::assertSame($fix, $this->listener->prePersistCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->prePersistCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->prePersistCalls[0][1]);
    }

    public function testPostPersistListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(2000);

        $this->listener->postPersistCalls = [];

        $this->_em->persist($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->postPersistCalls);
        self::assertSame($fix, $this->listener->postPersistCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->postPersistCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->postPersistCalls[0][1]);
    }

    public function testPostPersistCalledAfterAllInsertsHaveBeenPerformedAndIdsHaveBeenAssigned(): void
    {
        $object1 = new CompanyFixContract();
        $object1->setFixPrice(2000);

        $object2 = new CompanyPerson();
        $object2->setName('J. Doe');

        $this->_em->persist($object1);
        $this->_em->persist($object2);

        $listener = new class ([$object1, $object2]) {
            /** @var array<object> */
            private $trackedObjects;

            /** @var int */
            public $invocationCount = 0;

            public function __construct(array $trackedObjects)
            {
                $this->trackedObjects = $trackedObjects;
            }

            public function postPersist(PostPersistEventArgs $args): void
            {
                foreach ($this->trackedObjects as $object) {
                    Assert::assertNotNull($object->getId());
                }

                ++$this->invocationCount;
            }
        };

        $this->_em->getEventManager()->addEventListener(Events::postPersist, $listener);
        $this->_em->flush();

        self::assertSame(2, $listener->invocationCount);
    }

    public function testPreUpdateListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->preUpdateCalls = [];

        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->preUpdateCalls);
        self::assertSame($fix, $this->listener->preUpdateCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->preUpdateCalls[0][0]);
        self::assertInstanceOf(PreUpdateEventArgs::class, $this->listener->preUpdateCalls[0][1]);
    }

    public function testPostUpdateListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->postUpdateCalls = [];

        $fix->setFixPrice(2000);

        $this->_em->persist($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->postUpdateCalls);
        self::assertSame($fix, $this->listener->postUpdateCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->postUpdateCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->postUpdateCalls[0][1]);
    }

    public function testPreRemoveListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->preRemoveCalls = [];

        $this->_em->remove($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->preRemoveCalls);
        self::assertSame($fix, $this->listener->preRemoveCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->preRemoveCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->preRemoveCalls[0][1]);
    }

    public function testPostRemoveListeners(): void
    {
        $fix = new CompanyFixContract();
        $fix->setFixPrice(1000);

        $this->_em->persist($fix);
        $this->_em->flush();

        $this->listener->postRemoveCalls = [];

        $this->_em->remove($fix);
        $this->_em->flush();

        self::assertCount(1, $this->listener->postRemoveCalls);
        self::assertSame($fix, $this->listener->postRemoveCalls[0][0]);
        self::assertInstanceOf(CompanyFixContract::class, $this->listener->postRemoveCalls[0][0]);
        self::assertInstanceOf(LifecycleEventArgs::class, $this->listener->postRemoveCalls[0][1]);
    }

    public function testPostRemoveCalledAfterAllRemovalsHaveBeenPerformed(): void
    {
        $object1 = new CompanyFixContract();
        $object1->setFixPrice(2000);

        $object2 = new CompanyPerson();
        $object2->setName('J. Doe');

        $this->_em->persist($object1);
        $this->_em->persist($object2);
        $this->_em->flush();

        $listener = new class ($this->_em->getUnitOfWork(), [$object1, $object2]) {
            /** @var UnitOfWork */
            private $uow;

            /** @var array<object> */
            private $trackedObjects;

            /** @var int */
            public $invocationCount = 0;

            public function __construct(UnitOfWork $uow, array $trackedObjects)
            {
                $this->uow            = $uow;
                $this->trackedObjects = $trackedObjects;
            }

            public function postRemove(PostRemoveEventArgs $args): void
            {
                foreach ($this->trackedObjects as $object) {
                    Assert::assertFalse($this->uow->isInIdentityMap($object));
                }

                ++$this->invocationCount;
            }
        };

        $this->_em->getEventManager()->addEventListener(Events::postRemove, $listener);
        $this->_em->remove($object1);
        $this->_em->remove($object2);
        $this->_em->flush();

        self::assertSame(2, $listener->invocationCount);
    }
}
