<?php

namespace Doctrine\Tests\ORM\Cache;

use \Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Mapping\ClassMetadata;
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
            'createRegion'
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
            ->method('createRegion')
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
            ->method('createRegion')
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
            ->method('createRegion')
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
            ->method('createRegion')
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
            ->method('createRegion')
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
            ->method('createRegion')
            ->with($this->equalTo($mapping['cache']))
            ->will($this->returnValue($region));

        $cachedPersister = $this->factory->buildCachedCollectionPersister($em, $persister, $mapping);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\NonStrictReadWriteCachedCollectionPersister', $cachedPersister);
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
}