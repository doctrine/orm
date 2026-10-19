<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;

use function serialize;
use function unserialize;

#[Group('non-cacheable')]
class DeferredCollectionStorageTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DeferredStorageOwner::class, DeferredStorageTarget::class);
        $this->_em->getConnection()->executeStatement('DELETE FROM deferred_storage_links');
        $this->_em->createQuery('DELETE FROM ' . DeferredStorageOwner::class)->execute();
        $this->_em->createQuery('DELETE FROM ' . DeferredStorageTarget::class)->execute();

        $this->_em->persist(new DeferredStorageOwner());
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testHydrationAndFlushDoNotAllocateEmptyStorage(): void
    {
        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        self::assertInstanceOf(PersistentCollection::class, $owner->targets);
        self::assertFalse($owner->targets->isInitialized());
        $this->assertStorageIsDeferred($owner->targets);

        $this->getQueryLog()->reset()->enable();
        self::assertTrue($owner->targets->isEmptyWithoutInitialization());
        $this->_em->flush();

        $this->assertQueryCount(0);
        $this->assertStorageIsDeferred($owner->targets);

        $owner->name = 'changed';
        $this->_em->flush();

        $this->assertQueryCount(1);
        $this->assertStorageIsDeferred($owner->targets);
        self::assertFalse($owner->targets->isInitialized());
    }

    public function testAddingToDeferredCollectionStillCascadesPersist(): void
    {
        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        $owner->targets->add(new DeferredStorageTarget());

        self::assertTrue($owner->targets->isDirty());
        self::assertFalse($owner->targets->isInitialized());

        $this->_em->flush();
        $this->_em->clear();

        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        $this->assertStorageIsDeferred($owner->targets);
        self::assertCount(1, $owner->targets);
        self::assertSame(1, $owner->targets->first()->id);
        self::assertSame($owner->targets->toArray(), $owner->targets->getSnapshot());
        self::assertFalse($owner->targets->isDirty());
    }

    public function testEmptyDirtyCollectionIsStillUpdated(): void
    {
        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        $owner->targets->add(new DeferredStorageTarget());
        $this->_em->flush();
        $this->_em->clear();

        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        self::assertCount(1, $owner->targets);
        $owner->targets->removeElement($owner->targets->first());

        self::assertTrue($owner->targets->isDirty());
        self::assertTrue($owner->targets->isEmptyWithoutInitialization());
        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(0, $this->_em->find(DeferredStorageOwner::class, 1)->targets);
    }

    public function testCloneAndSerializationOfDeferredCollection(): void
    {
        $owner = $this->_em->find(DeferredStorageOwner::class, 1);
        $clone = clone $owner->targets;

        self::assertNull($clone->getOwner());
        self::assertTrue($clone->isInitialized());
        self::assertCount(0, $clone);

        $this->assertStorageIsDeferred($owner->targets);
        $restored = unserialize(serialize($owner->targets));
        self::assertCount(0, $restored);
    }

    private function assertStorageIsDeferred(PersistentCollection $collection): void
    {
        $property = new ReflectionProperty(AbstractLazyCollection::class, 'collection');
        self::assertNull($property->getValue($collection));
    }
}

#[ORM\Entity]
class DeferredStorageOwner
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\Column]
    public string $name = 'owner';

    /** @var Collection<int, DeferredStorageTarget> */
    #[ORM\ManyToMany(targetEntity: DeferredStorageTarget::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'deferred_storage_links')]
    public Collection $targets;

    public function __construct()
    {
        $this->targets = new ArrayCollection();
    }
}

#[ORM\Entity]
class DeferredStorageTarget
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;
}
