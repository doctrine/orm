<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\DoctrineTestCase;
use Doctrine\ORM\Cache\CacheConfiguration;

/**
 * @group DDC-2183
 *
 * @covers \Doctrine\ORM\Cache\CacheConfiguration
 */
class CacheConfigTest extends DoctrineTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\CacheConfiguration
     */
    private $config;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->config = new CacheConfiguration();
    }

    /**
     * @covers \Doctrine\ORM\Cache\CacheConfiguration::getCacheInstantiator
     */
    public function testGetDefaultCacheIstantiator()
    {
        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $config        = $this->getMock('Doctrine\ORM\Configuration');

        $entityManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($config));
        $config
            ->expects($this->any())
            ->method('getSecondLevelCacheConfiguration')
            ->will($this->returnValue($this->config));

        $defaultIstantiator = $this->config->getCacheInstantiator();

        $this->assertInstanceOf('Doctrine\ORM\Cache\DefaultCache', $defaultIstantiator($entityManager));
    }

    /**
     * @covers \Doctrine\ORM\Cache\CacheConfiguration::getCacheInstantiator
     */
    public function testSetGetCacheIstantiator()
    {
        $istantiator = function () {};

        $this->config->setCacheInstantiator($istantiator);
        $this->assertSame($istantiator, $this->config->getCacheInstantiator());

        $this->setExpectedException('Doctrine\ORM\ORMException');

        $this->config->setCacheInstantiator(null);
    }

    public function testSetGetRegionLifetime()
    {
        $config = $this->config->getRegionsConfiguration();

        $config->setDefaultLifetime(111);

        $this->assertEquals($config->getDefaultLifetime(), $config->getLifetime('foo_region'));

        $config->setLifetime('foo_region', 222);

        $this->assertEquals(222, $config->getLifetime('foo_region'));
    }

    public function testSetGetCacheLogger()
    {
        $logger = $this->getMock('Doctrine\ORM\Cache\Logging\CacheLogger');

        $this->assertNull($this->config->getCacheLogger());

        $this->config->setCacheLogger($logger);

        $this->assertEquals($logger, $this->config->getCacheLogger());
    }

    public function testSetGetCacheFactory()
    {
        $factory = $this->getMock('Doctrine\ORM\Cache\CacheFactory');

        $this->assertNull($this->config->getCacheFactory());

        $this->config->setCacheFactory($factory);

        $this->assertEquals($factory, $this->config->getCacheFactory());
    }

    public function testSetGetQueryValidator()
    {
        $validator = $this->getMock('Doctrine\ORM\Cache\QueryCacheValidator');

        $this->assertInstanceOf('Doctrine\ORM\Cache\TimestampQueryCacheValidator', $this->config->getQueryValidator());

        $this->config->setQueryValidator($validator);

        $this->assertEquals($validator, $this->config->getQueryValidator());
    }
}