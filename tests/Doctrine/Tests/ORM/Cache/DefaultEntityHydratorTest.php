<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\AssociationCacheEntry;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\ORM\Cache\DefaultEntityHydrator;
use Doctrine\ORM\Cache\EntityCacheEntry;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\EntityHydrator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmTestCase;

/** @group DDC-2183 */
class DefaultEntityHydratorTest extends OrmTestCase
{
    /** @var EntityHydrator */
    private $structure;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em        = $this->getTestEntityManager();
        $this->structure = new DefaultEntityHydrator($this->em);
    }

    public function testImplementsEntityEntryStructure(): void
    {
        self::assertInstanceOf('\Doctrine\ORM\Cache\EntityHydrator', $this->structure);
    }

    public function testCreateEntity(): void
    {
        $metadata = $this->em->getClassMetadata(Country::class);
        $key      = new EntityCacheKey($metadata->name, ['id' => 1]);
        $entry    = new EntityCacheEntry($metadata->name, ['id' => 1, 'name' => 'Foo']);
        $entity   = $this->structure->loadCacheEntry($metadata, $key, $entry);

        self::assertInstanceOf($metadata->name, $entity);

        self::assertEquals(1, $entity->getId());
        self::assertEquals('Foo', $entity->getName());
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($entity));
    }

    public function testLoadProxy(): void
    {
        $metadata = $this->em->getClassMetadata(Country::class);
        $key      = new EntityCacheKey($metadata->name, ['id' => 1]);
        $entry    = new EntityCacheEntry($metadata->name, ['id' => 1, 'name' => 'Foo']);
        $proxy    = $this->em->getReference($metadata->name, $key->identifier);
        $entity   = $this->structure->loadCacheEntry($metadata, $key, $entry, $proxy);

        self::assertInstanceOf($metadata->name, $entity);
        self::assertSame($proxy, $entity);

        self::assertEquals(1, $entity->getId());
        self::assertEquals('Foo', $entity->getName());
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->em->getUnitOfWork()->getEntityState($proxy));
    }

    public function testBuildCacheEntry(): void
    {
        $entity   = new Country('Foo');
        $uow      = $this->em->getUnitOfWork();
        $data     = ['id' => 1, 'name' => 'Foo'];
        $metadata = $this->em->getClassMetadata(Country::class);
        $key      = new EntityCacheKey($metadata->name, ['id' => 1]);

        $entity->setId(1);
        $uow->registerManaged($entity, $key->identifier, $data);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf(CacheEntry::class, $cache);
        self::assertInstanceOf(EntityCacheEntry::class, $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertEquals(
            [
                'id'   => 1,
                'name' => 'Foo',
            ],
            $cache->data
        );
    }

    public function testBuildCacheEntryAssociation(): void
    {
        $country     = new Country('Foo');
        $state       = new State('Bat', $country);
        $uow         = $this->em->getUnitOfWork();
        $countryData = ['id' => 11, 'name' => 'Foo'];
        $stateData   = ['id' => 12, 'name' => 'Bar', 'country' => $country];
        $metadata    = $this->em->getClassMetadata(State::class);
        $key         = new EntityCacheKey($metadata->name, ['id' => 11]);

        $country->setId(11);
        $state->setId(12);

        $uow->registerManaged($country, ['id' => 11], $countryData);
        $uow->registerManaged($state, ['id' => 12], $stateData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $state);

        self::assertInstanceOf(CacheEntry::class, $cache);
        self::assertInstanceOf(EntityCacheEntry::class, $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertEquals(
            [
                'id'        => 12,
                'name'      => 'Bar',
                'country'   => new AssociationCacheEntry(Country::class, ['id' => 11]),
            ],
            $cache->data
        );
    }

    public function testBuildCacheEntryNonInitializedAssocProxy(): void
    {
        $proxy      = $this->em->getReference(Country::class, 11);
        $entity     = new State('Bat', $proxy);
        $uow        = $this->em->getUnitOfWork();
        $entityData = ['id' => 12, 'name' => 'Bar', 'country' => $proxy];
        $metadata   = $this->em->getClassMetadata(State::class);
        $key        = new EntityCacheKey($metadata->name, ['id' => 11]);

        $entity->setId(12);

        $uow->registerManaged($entity, ['id' => 12], $entityData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf(CacheEntry::class, $cache);
        self::assertInstanceOf(EntityCacheEntry::class, $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertEquals(
            [
                'id'        => 12,
                'name'      => 'Bar',
                'country'   => new AssociationCacheEntry(Country::class, ['id' => 11]),
            ],
            $cache->data
        );
    }

    public function testCacheEntryWithWrongIdentifierType(): void
    {
        $proxy      = $this->em->getReference(Country::class, 11);
        $entity     = new State('Bat', $proxy);
        $uow        = $this->em->getUnitOfWork();
        $entityData = ['id' => 12, 'name' => 'Bar', 'country' => $proxy];
        $metadata   = $this->em->getClassMetadata(State::class);
        $key        = new EntityCacheKey($metadata->name, ['id' => '12']);

        $entity->setId(12);

        $uow->registerManaged($entity, ['id' => 12], $entityData);

        $cache = $this->structure->buildCacheEntry($metadata, $key, $entity);

        self::assertInstanceOf(CacheEntry::class, $cache);
        self::assertInstanceOf(EntityCacheEntry::class, $cache);

        self::assertArrayHasKey('id', $cache->data);
        self::assertArrayHasKey('name', $cache->data);
        self::assertArrayHasKey('country', $cache->data);
        self::assertSame($entity->getId(), $cache->data['id']);
        self::assertEquals(
            [
                'id'        => 12,
                'name'      => 'Bar',
                'country'   => new AssociationCacheEntry(Country::class, ['id' => 11]),
            ],
            $cache->data
        );
    }
}
