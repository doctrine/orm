<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache\Persister\Collection;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\Cache\Persister\Collection\AbstractCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Persisters\Collection\CollectionPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/** @group DDC-2183 */
abstract class CollectionPersisterTestCase extends OrmTestCase
{
    /** @var Region&MockObject */
    protected $region;

    /** @var CollectionPersister&MockObject */
    protected $collectionPersister;

    /** @var EntityManagerMock */
    protected $em;

    abstract protected function createPersister(EntityManagerInterface $em, CollectionPersister $persister, Region $region, array $mapping): AbstractCollectionPersister;

    protected function setUp(): void
    {
        $this->getSharedSecondLevelCache()->clear();
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->em                  = $this->getTestEntityManager();
        $this->region              = $this->createRegion();
        $this->collectionPersister = $this->createMock(CollectionPersister::class);
    }

    /** @return Region&MockObject */
    protected function createRegion(): Region
    {
        return $this->createMock(Region::class);
    }

    /** @param object $owner */
    protected function createCollection($owner): PersistentCollection
    {
        $class = $this->em->getClassMetadata(State::class);
        $assoc = $class->associationMappings['cities'];
        $coll  = new PersistentCollection($this->em, $class, new ArrayCollection());

        $coll->setOwner($owner, $assoc);
        $coll->setInitialized(true);

        return $coll;
    }

    protected function createPersisterDefault(): AbstractCollectionPersister
    {
        $assoc = $this->em->getClassMetadata(State::class)->associationMappings['cities'];

        return $this->createPersister($this->em, $this->collectionPersister, $this->region, $assoc);
    }

    public function testImplementsEntityPersister(): void
    {
        $persister = $this->createPersisterDefault();

        self::assertInstanceOf(CollectionPersister::class, $persister);
        self::assertInstanceOf(CachedPersister::class, $persister);
        self::assertInstanceOf(CachedCollectionPersister::class, $persister);
    }

    public function testInvokeDelete(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('delete')
            ->with(self::identicalTo($collection));

        $persister->delete($collection);
    }

    public function testInvokeUpdate(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $collection->setDirty(true);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('update')
            ->with(self::identicalTo($collection));

        $persister->update($collection);
    }

    public function testInvokeCount(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('count')
            ->with(self::identicalTo($collection))
            ->willReturn(0);

        self::assertSame(0, $persister->count($collection));
    }

    public function testInvokeSlice(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);
        $slice      = [(object) [], (object) []];

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('slice')
            ->with(self::identicalTo($collection), self::identicalTo(1), self::identicalTo(2))
            ->willReturn($slice);

        self::assertEquals($slice, $persister->slice($collection, 1, 2));
    }

    public function testInvokeContains(): void
    {
        $entity     = new State('Foo');
        $element    = new State('Bar');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('contains')
            ->with(self::identicalTo($collection), self::identicalTo($element))
            ->willReturn(false);

        self::assertFalse($persister->contains($collection, $element));
    }

    public function testInvokeContainsKey(): void
    {
        $entity     = new State('Foo');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('containsKey')
            ->with(self::identicalTo($collection), self::identicalTo(0))
            ->willReturn(false);

        self::assertFalse($persister->containsKey($collection, 0));
    }

    public function testInvokeGet(): void
    {
        $entity     = new State('Foo');
        $element    = new State('Bar');
        $persister  = $this->createPersisterDefault();
        $collection = $this->createCollection($entity);

        $this->em->getUnitOfWork()->registerManaged($entity, ['id' => 1], ['id' => 1, 'name' => 'Foo']);

        $this->collectionPersister->expects(self::once())
            ->method('get')
            ->with(self::identicalTo($collection), self::identicalTo(0))
            ->willReturn($element);

        self::assertEquals($element, $persister->get($collection, 0));
    }
}
