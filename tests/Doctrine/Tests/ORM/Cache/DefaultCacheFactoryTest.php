<?php

namespace Doctrine\Tests\ORM\Cache;

use \Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Persisters\BasicEntityPersister;
use Doctrine\ORM\Persisters\OneToManyPersister;

/**
 * @group DDC-2183
 */
class DefaultCacheFactoryTest extends OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\CacheFactory
     */
    private $factory;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em = $this->_getTestEntityManager();


        $arguments     = array($this->em->getConfiguration(), $this->getSharedSecondLevelCacheDriverImpl());
        $this->factory = $this->getMock('\Doctrine\ORM\Cache\DefaultCacheFactory', array(
            'getRegion'
        ), $arguments);
    }

    public function testInplementsCacheFactory()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\CacheFactory', $this->factory);
    }

    public function testBuildCachedEntityPersisterReadOnly()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = clone $em->getClassMetadata($entityName);
        $persister  = new BasicEntityPersister($em, $metadata);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($metadata->cache))
            ->will($this->returnValue($region));

        
        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\ReadOnlyCachedEntityPersister', $cachedPersister);
    }

    public function testBuildCachedEntityPersisterReadWrite()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = clone $em->getClassMetadata($entityName);
        $persister  = new BasicEntityPersister($em, $metadata);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($metadata->cache))
            ->will($this->returnValue($region));

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\ReadWriteCachedEntityPersister', $cachedPersister);
    }

    public function testBuildCachedEntityPersisterNonStrictReadWrite()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = clone $em->getClassMetadata($entityName);
        $persister  = new BasicEntityPersister($em, $metadata);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $metadata->cache['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($metadata->cache))
            ->will($this->returnValue($region));

        $cachedPersister = $this->factory->buildCachedEntityPersister($em, $persister, $metadata);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedEntityPersister', $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterReadOnly()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = $em->getClassMetadata($entityName);
        $mapping    = $metadata->associationMappings['cities'];
        $persister  = new OneToManyPersister($em);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_READ_ONLY;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($mapping['cache']))
            ->will($this->returnValue($region));


        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\ReadOnlyCachedCollectionPersister', $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterReadWrite()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = $em->getClassMetadata($entityName);
        $mapping    = $metadata->associationMappings['cities'];
        $persister  = new OneToManyPersister($em);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_READ_WRITE;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($mapping['cache']))
            ->will($this->returnValue($region));

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\ReadWriteCachedCollectionPersister', $cachedPersister);
    }

    public function testBuildCachedCollectionPersisterNonStrictReadWrite()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = $em->getClassMetadata($entityName);
        $mapping    = $metadata->associationMappings['cities'];
        $persister  = new OneToManyPersister($em);
        $region     = new ConcurrentRegionMock(new DefaultRegion('regionName', $this->getSharedSecondLevelCacheDriverImpl()));

        $mapping['cache']['usage'] = ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE;

        $this->factory->expects($this->once())
            ->method('getRegion')
            ->with($this->equalTo($mapping['cache']))
            ->will($this->returnValue($region));

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedCollectionPersister', $cachedPersister);
    }

    public function testInheritedEntityCacheRegion()
    {
        $em         = $this->em;
        $metadata1  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\AttractionContactInfo');
        $metadata2  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\AttractionLocationInfo');
        $persister1 = new BasicEntityPersister($em, $metadata1);
        $persister2 = new BasicEntityPersister($em, $metadata2);
        $factory    = new DefaultCacheFactory($this->em->getConfiguration(), $this->getSharedSecondLevelCacheDriverImpl());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister1);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister2);

        $this->assertNotSame($cachedPersister1, $cachedPersister2);
        $this->assertSame($cachedPersister1->getCacheRegion(), $cachedPersister2->getCacheRegion());
    }

    public function testCreateNewCacheDriver()
    {
        $em         = $this->em;
        $metadata1  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\State');
        $metadata2  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\City');
        $persister1 = new BasicEntityPersister($em, $metadata1);
        $persister2 = new BasicEntityPersister($em, $metadata2);
        $factory    = new DefaultCacheFactory($this->em->getConfiguration(), $this->getSharedSecondLevelCacheDriverImpl());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister1);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedEntityPersister', $cachedPersister2);

        $this->assertNotSame($cachedPersister1, $cachedPersister2);
        $this->assertNotSame($cachedPersister1->getCacheRegion(), $cachedPersister2->getCacheRegion());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unrecognized access strategy type [-1]
     */
    public function testBuildCachedEntityPersisterNonStrictException()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = clone $em->getClassMetadata($entityName);
        $persister  = new BasicEntityPersister($em, $metadata);

        $metadata->cache['usage'] = -1;

        $this->factory->buildCachedEntityPersister($em, $persister, $metadata);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Unrecognized access strategy type [-1]
     */
    public function testBuildCachedCollectionPersisterException()
    {
        $em         = $this->em;
        $entityName = 'Doctrine\Tests\Models\Cache\State';
        $metadata   = $em->getClassMetadata($entityName);
        $mapping    = $metadata->associationMappings['cities'];
        $persister  = new OneToManyPersister($em);

        $mapping['cache']['usage'] = -1;

        $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage To use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" is required, The default implementation provided by doctrine is "Doctrine\ORM\Cache\Region\FileLockRegion" if you what to use it please provide a valid directory
     */
    public function testInvalidFileLockRegionDirectoryException()
    {
        $factory = new \Doctrine\ORM\Cache\DefaultCacheFactory($this->em->getConfiguration(), $this->getSharedSecondLevelCacheDriverImpl());

        $factory->getRegion(array(
            'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
            'region'  => 'foo'
        ));
    }
}