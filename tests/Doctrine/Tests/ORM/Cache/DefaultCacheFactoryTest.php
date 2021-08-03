<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
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
use Doctrine\ORM\Cache\Region\DefaultMultiGetRegion;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\RegionsConfiguration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\Tests\Models\Cache\AttractionContactInfo;
use Doctrine\Tests\Models\Cache\AttractionLocationInfo;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmTestCase;
use InvalidArgumentException;
use LogicException;

use function assert;

/**
 * @group DDC-2183
 */
class DefaultCacheFactoryTest extends OrmTestCase
{
    /** @var CacheFactory */
    private $factory;

    /** @var EntityManager */
    private $em;

    /** @var RegionsConfiguration */
    private $regionsConfig;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em            = $this->getTestEntityManager();
        $this->regionsConfig = new RegionsConfiguration();
        $arguments           = [$this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl()];
        $this->factory       = $this->getMockBuilder(DefaultCacheFactory::class)
                                    ->setMethods(['getRegion'])
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
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->will(self::returnValue($region));

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadOnlyCachedEntityPersister::class, $cachedPersister);
    }

    public function testBuildCachedEntityPersisterReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->will(self::returnValue($region));

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        self::assertInstanceOf(CachedEntityPersister::class, $cachedPersister);
        self::assertInstanceOf(ReadWriteCachedEntityPersister::class, $cachedPersister);
    }

    public function testBuildCachedEntityPersisterNonStrictReadWrite(): void
    {
        $em        = $this->em;
        $metadata  = clone $em->getClassMetadata(State::class);
        $persister = new BasicEntityPersister($em, $metadata);
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($metadata->cache))
            ->will(self::returnValue($region));

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
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping['cache']))
            ->will(self::returnValue($region));

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
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping['cache']))
            ->will(self::returnValue($region));

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
        $region    = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects(self::once())
            ->method('getRegion')
            ->with(self::equalTo($mapping['cache']))
            ->will(self::returnValue($region));

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
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

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
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

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

        $mapping['cache']['usage'] = -1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unrecognized access strategy type [-1]');

        $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);
    }

    public function testInvalidFileLockRegionDirectoryException(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'If you want to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" '
            . 'is required, The default implementation provided by doctrine is '
            . '"Doctrine\ORM\Cache\Region\FileLockRegion" if you want to use it please provide a valid directory'
        );

        $factory->getRegion(
            [
                'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
                'region'  => 'foo',
            ]
        );
    }

    public function testInvalidFileLockRegionDirectoryExceptionWithEmptyString(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $factory->setFileLockRegionDirectory('');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'If you want to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" '
            . 'is required, The default implementation provided by doctrine is '
            . '"Doctrine\ORM\Cache\Region\FileLockRegion" if you want to use it please provide a valid directory'
        );

        $factory->getRegion(
            [
                'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
                'region'  => 'foo',
            ]
        );
    }

    public function testBuildsNewNamespacedCacheInstancePerRegionInstance(): void
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $fooRegion = $factory->getRegion(
            [
                'region' => 'foo',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ]
        );
        $barRegion = $factory->getRegion(
            [
                'region' => 'bar',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ]
        );

        self::assertSame('foo', $fooRegion->getCache()->getNamespace());
        self::assertSame('bar', $barRegion->getCache()->getNamespace());
    }

    public function testAppendsNamespacedCacheInstancePerRegionInstanceWhenItsAlreadySet(): void
    {
        $cache = clone $this->getSharedSecondLevelCacheDriverImpl();
        $cache->setNamespace('testing');

        $factory = new DefaultCacheFactory($this->regionsConfig, $cache);

        $fooRegion = $factory->getRegion(
            [
                'region' => 'foo',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ]
        );
        $barRegion = $factory->getRegion(
            [
                'region' => 'bar',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ]
        );

        self::assertSame('testing:foo', $fooRegion->getCache()->getNamespace());
        self::assertSame('testing:bar', $barRegion->getCache()->getNamespace());
    }

    public function testBuildsDefaultCacheRegionFromGenericCacheRegion(): void
    {
        $cache = $this->createMock(Cache::class);
        assert($cache instanceof Cache);

        $factory = new DefaultCacheFactory($this->regionsConfig, $cache);

        self::assertInstanceOf(
            DefaultRegion::class,
            $factory->getRegion(
                [
                    'region' => 'bar',
                    'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
                ]
            )
        );
    }

    public function testBuildsMultiGetCacheRegionFromGenericCacheRegion(): void
    {
        $cache = $this->getMockForAbstractClass(CacheProvider::class);
        assert($cache instanceof CacheProvider);

        $factory = new DefaultCacheFactory($this->regionsConfig, $cache);

        self::assertInstanceOf(
            DefaultMultiGetRegion::class,
            $factory->getRegion(
                [
                    'region' => 'bar',
                    'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
                ]
            )
        );
    }
}
