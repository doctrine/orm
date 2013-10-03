<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Tests\DoctrineTestCase;
use Doctrine\ORM\Cache\CacheConfiguration;

/**
 * @group DDC-2183
 */
class CacheConfigTest extends DoctrineTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\CacheConfiguration
     */
    private $config;

    protected function setUp()
    {
        parent::setUp();

        $this->config = new CacheConfiguration();
    }

    public function testSetGetCacheClassName()
    {
        $mockClass = get_class($this->getMock('Doctrine\ORM\Cache'));

        $this->assertEquals('Doctrine\ORM\Cache\DefaultCache', $this->config->getCacheClassName());
        $this->config->setCacheClassName($mockClass);
        $this->assertEquals($mockClass, $this->config->getCacheClassName());

        $this->setExpectedException('Doctrine\ORM\ORMException');
        $this->config->setCacheClassName(__CLASS__);
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