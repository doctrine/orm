<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\DefaultCacheInstantiator;
use Doctrine\Tests\OrmTestCase;

/**
 * @covers \Doctrine\ORM\Cache\DefaultCacheInstantiator
 */
class DefaultCacheInstantiatorTest extends OrmTestCase
{
    public function testGetCache()
    {
        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $config        = $this->getMock('Doctrine\ORM\Configuration');
        $cacheConfig   = $this->getMock('Doctrine\ORM\Cache\CacheConfiguration');

        $entityManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($config));
        $config
            ->expects($this->any())
            ->method('getSecondLevelCacheConfiguration')
            ->will($this->returnValue($cacheConfig));

        $instantiator = new DefaultCacheInstantiator();

        $this->assertInstanceOf('Doctrine\ORM\Cache\DefaultCache', $instantiator->getCache($entityManager));
    }
}
