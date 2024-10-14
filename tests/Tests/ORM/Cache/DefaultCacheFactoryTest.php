<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadOnlyCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister;
use Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister;
use Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Cache\AttractionContactInfo;
use Doctrine\Tests\Models\Cache\AttractionLocationInfo;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmTestCase;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;

#[Group('DDC-2183')]
class DefaultCacheFactoryTest extends OrmTestCase
{
    private CacheFactory&MockObject $factory;
    private EntityManagerMock $em;
    private RegionsConfiguration $regionsConfig;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->em            = $this->getTestEntityManager();
        $this->regionsConfig = new RegionsConfiguration();
        $arguments           = [$this->regionsConfig, $this->getSharedSecondLevelCache()];
        $this->factory       = $this->getMockBuilder(DefaultCacheFactory::class)
                                    ->onlyMethods(['getRegion'])
                                    ->setConstructorArgs($arguments)
                                    ->getMock();
    }

    public function testImplementsCacheFactory(): void
    {
        self::assertInstanceOf(CacheFactory::class, $this->factory);
    }

    public function testBuildCachedEntityPersisterReadOnly(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadOnlyCachedEntityPersister::class, $cachedPersister);
    }

    public function testBuildCachedEntityPersisterReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadWriteCachedEntityPersister::class, $cachedPersister);
    }

    public function testBuildCachedEntityPersisterNonStrictReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister);
        self::assertInstanceOf(NonStrictReadWriteCachedEntityPersister::class, $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterReadOnly(): void
    {
        $em        = $this->em;
        $metadata  = $em->getClassMetadata(State::class);
        $mapping   = $metadata->associationMappings['cities'];
        $persister = new OneToManyPersister($em);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $mapping->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        self::assertInstanceOf(CachedCollectionPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadOnlyCachedCollectionPersister::class, $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = $em->getClassMetadata(State::class);
        $mapping   = $metadata->associationMappings['cities'];
        $persister = new OneToManyPersister($em);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $mapping->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        self::assertInstanceOf(CachedCollectionPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadWriteCachedCollectionPersister::class, $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterNonStrictReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = $em->getClassMetadata(State::class);
        $mapping   = $metadata->associationMappings['cities'];
        $persister = new OneToManyPersister($em);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCache()));

        $mapping->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping->cache))
            ->willReturn($region);

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        self::assertInstanceOf(CachedCollectionPersister::class, $cachedPersister);
        self::assertInstanceOf(NonStrictReadWriteCachedCollectionPersister::class, $cachedPersister);
    }

    public function testInheritedEntityCacheRegion(): void
    {
        $em         = $this->em;
        $metadata1  = clone $em->getClassMetadata(AttractionContactInfo::class);
        $metadata2  = clone $em->getClassMetadata(AttractionLocationInfo::class);
        $persister1 = new BasicEntityPersister($em, $metadata1);
        $persister2 = new BasicEntityPersister($em, $metadata2);
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCache());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister1);
        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister2);

        self::assertNotSame($cachedPersister1, $cachedPersister2);
        self::assertSame($cachedPersister1->getCacheRegion(), $cachedPersister2->getCacheRegion());
    }

    public function testCreateNewCacheDriver(): void
    {
        $em         = $this->em;
        $metadata1  = clone $em->getClassMetadata(State::class);
        $metadata2  = clone $em->getClassMetadata(City::class);
        $persister1 = new BasicEntityPersister($em, $metadata1);
        $persister2 = new BasicEntityPersister($em, $metadata2);
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCache());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister1);
        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister2);

        self::assertNotSame($cachedPersister1, $cachedPersister2);
        self::assertNotSame($cachedPersister1->getCacheRegion(), $cachedPersister2->getCacheRegion());
    }

    public function testBuildCachedEntityPersisterNonStrictException(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);

        $metadata->cache['usage'] = -1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized access strategy type [-1]');

        $this->factory->buildCachedEntityPersister($em, $persister, $metadata);
    }

    public function testBuildCachedCollectionPersisterException(): void
    {
        $em        = $this->em;
        $metadata  = $em->getClassMetadata(State::class);
        $mapping   = $metadata->associationMappings['cities'];
        $persister = new OneToManyPersister($em);

        $mapping->cache['usage'] = -1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized access strategy type [-1]');

        $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);
    }

    public function testInvalidFileLockRegionDirectoryException(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCache());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'If you want to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" '
            . 'is required, The default implementation provided by doctrine is '
            . '"Doctrine\ORM\Cache\Region\FileLockRegion" if you want to use it please provide a valid directory',
        );

        $factory->getRegion(
            [
                'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
                'region'  => 'foo',
            ],
        );
    }

    public function testInvalidFileLockRegionDirectoryExceptionWithEmptyString(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCache());

        $factory->setFileLockRegionDirectory('');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'If you want to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" '
            . 'is required, The default implementation provided by doctrine is '
            . '"Doctrine\ORM\Cache\Region\FileLockRegion" if you want to use it please provide a valid directory',
        );

        $factory->getRegion(
            [
                'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
                'region'  => 'foo',
            ],
        );
    }

    public function testBuildsDefaultCacheRegionFromGenericCacheRegion(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->createMock(CacheItemPoolInterface::class));

        self::assertInstanceOf(
            DefaultRegion::class,
            $factory->getRegion(
                [
                    'region' => 'bar',
                    'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
                ],
            ),
        );
    }
}
