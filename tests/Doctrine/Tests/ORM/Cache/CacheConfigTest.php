<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\ORM\Cache\QueryCacheValidator;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\TimestampQueryCacheValidator;
use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\Tests\DoctrineTestCase;

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
        $logger = $this->createMock(CacheLogger::class);

        self::assertNull($this->config->getCacheLogger());

        $this->config->setCacheLogger($logger);

        self::assertEquals($logger, $this->config->getCacheLogger());
    }

    public function testSetGetCacheFactory()
    {
        $factory = $this->createMock(CacheFactory::class);

        self::assertNull($this->config->getCacheFactory());

        $this->config->setCacheFactory($factory);

        self::assertEquals($factory, $this->config->getCacheFactory());
    }

    public function testSetGetQueryValidator()
    {
        $factory = $this->createMock(CacheFactory::class);
        $factory->method('getTimestampRegion')->willReturn($this->createMock(TimestampRegion::class));

        $this->config->setCacheFactory($factory);

        $validator = $this->createMock(QueryCacheValidator::class);

        self::assertInstanceOf(TimestampQueryCacheValidator::class, $this->config->getQueryValidator());

        $this->config->setQueryValidator($validator);

        self::assertEquals($validator, $this->config->getQueryValidator());
    }
}
