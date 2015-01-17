<?php

namespace Doctrine\Tests\ORM\Cache;

use \Doctrine\Tests\OrmTestCase;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\ORM\Persisters\Collection\OneToManyPersister;
use Doctrine\ORM\Cache\RegionsConfiguration;

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

    /**
     * @var \Doctrine\ORM\Cache\RegionsConfiguration
     */
    private $regionsConfig;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->em               = $this->_getTestEntityManager();
        $this->regionsConfig    = new RegionsConfiguration;
        $arguments              = array($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());
        $this->factory          = $this->getMock('\Doctrine\ORM\Cache\DefaultCacheFactory', array(
            'getRegion'
        ), $arguments);
    }

    public function testImplementsCacheFactory()
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\ReadOnlyCachedEntityPersister', $cachedPersister);
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\ReadWriteCachedEntityPersister', $cachedPersister);
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\NonStrictReadWriteCachedEntityPersister', $cachedPersister);
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\ReadOnlyCachedCollectionPersister', $cachedPersister);
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\ReadWriteCachedCollectionPersister', $cachedPersister);
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

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\CachedCollectionPersister', $cachedPersister);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Collection\NonStrictReadWriteCachedCollectionPersister', $cachedPersister);
    }

    public function testInheritedEntityCacheRegion()
    {
        $em         = $this->em;
        $metadata1  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\AttractionContactInfo');
        $metadata2  = clone $em->getClassMetadata('Doctrine\Tests\Models\Cache\AttractionLocationInfo');
        $persister1 = new BasicEntityPersister($em, $metadata1);
        $persister2 = new BasicEntityPersister($em, $metadata2);
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister1);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister2);

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
        $factory    = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $cachedPersister1 = $factory->buildCachedEntityPersister($em, $persister1, $metadata1);
        $cachedPersister2 = $factory->buildCachedEntityPersister($em, $persister2, $metadata2);

        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister1);
        $this->assertInstanceOf('Doctrine\ORM\Cache\Persister\Entity\CachedEntityPersister', $cachedPersister2);

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
     * @expectedException LogicException
     * @expectedExceptionMessage If you what to use a "READ_WRITE" cache an implementation of "Doctrine\ORM\Cache\ConcurrentRegion" is required, The default implementation provided by doctrine is "Doctrine\ORM\Cache\Region\FileLockRegion" if you what to use it please provide a valid directory
     */
    public function testInvalidFileLockRegionDirectoryException()
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $factory->getRegion(array(
            'usage'   => ClassMetadata::CACHE_USAGE_READ_WRITE,
            'region'  => 'foo'
        ));
    }

    public function testBuildsNewNamespacedCacheInstancePerRegionInstance()
    {
        $factory = new DefaultCacheFactory($this->regionsConfig, $this->getSharedSecondLevelCacheDriverImpl());

        $fooRegion = $factory->getRegion(array(
            'region' => 'foo',
            'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
        ));
        $barRegion = $factory->getRegion(array(
            'region' => 'bar',
            'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
        ));

        $this->assertSame('foo', $fooRegion->getCache()->getNamespace());
        $this->assertSame('bar', $barRegion->getCache()->getNamespace());
    }

    public function testBuildsDefaultCacheRegionFromGenericCacheRegion()
    {
        /* @var $cache \Doctrine\Common\Cache\Cache */
        $cache = $this->getMock('Doctrine\Common\Cache\Cache');

        $factory = new DefaultCacheFactory($this->regionsConfig, $cache);

        $this->assertInstanceOf(
            'Doctrine\ORM\Cache\Region\DefaultRegion',
            $factory->getRegion(array(
                'region' => 'bar',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ))
        );
    }

    public function testBuildsMultiGetCacheRegionFromGenericCacheRegion()
    {
        /* @var $cache \Doctrine\Common\Cache\CacheProvider */
        $cache = $this->getMockForAbstractClass('Doctrine\Common\Cache\CacheProvider');

        $factory = new DefaultCacheFactory($this->regionsConfig, $cache);

        $this->assertInstanceOf(
            'Doctrine\ORM\Cache\Region\DefaultMultiGetRegion',
            $factory->getRegion(array(
                'region' => 'bar',
                'usage'  => ClassMetadata::CACHE_USAGE_READ_ONLY,
            ))
        );
    }

}
