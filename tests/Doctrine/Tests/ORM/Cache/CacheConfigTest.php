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

    public function testSetGetRegionLifetime()
    {
        $config = $this->config->getRegionsConfiguration();

        $config->setDefaultLifetime(111);

        self::assertEquals($config->getDefaultLifetime(), $config->getLifetime('foo_region'));

        $config->setLifetime('foo_region', 222);

        self::assertEquals(222, $config->getLifetime('foo_region'));
    }

    public function testSetGetCacheLogger()
    {
        $logger = $this->getMock('Doctrine\ORM\Cache\Logging\CacheLogger');

        self::assertNull($this->config->getCacheLogger());

        $this->config->setCacheLogger($logger);

        self::assertEquals($logger, $this->config->getCacheLogger());
    }

    public function testSetGetCacheFactory()
    {
        $factory = $this->getMock('Doctrine\ORM\Cache\CacheFactory');

        self::assertNull($this->config->getCacheFactory());

        $this->config->setCacheFactory($factory);

        self::assertEquals($factory, $this->config->getCacheFactory());
    }

    public function testSetGetQueryValidator()
    {
        $validator = $this->getMock('Doctrine\ORM\Cache\QueryCacheValidator');

        self::assertInstanceOf('Doctrine\ORM\Cache\TimestampQueryCacheValidator', $this->config->getQueryValidator());

        $this->config->setQueryValidator($validator);

        self::assertEquals($validator, $this->config->getQueryValidator());
    }
}