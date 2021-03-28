<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\CacheFactory;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\QueryCacheValidator;
use Doctrine\ORM\Cache\TimestampQueryCacheValidator;
use Doctrine\ORM\Cache\TimestampRegion;
use Doctrine\Tests\DoctrineTestCase;

/**
 * @group DDC-2183
 * @covers \Doctrine\ORM\Cache\CacheConfiguration
 */
class CacheConfigTest extends DoctrineTestCase
{
    /** @var CacheConfiguration */
    private $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new CacheConfiguration();
    }

    public function testSetGetRegionLifetime(): void
    {
        $config = $this->config->getRegionsConfiguration();

        $config->setDefaultLifetime(111);

        $this->assertEquals($config->getDefaultLifetime(), $config->getLifetime('foo_region'));

        $config->setLifetime('foo_region', 222);

        $this->assertEquals(222, $config->getLifetime('foo_region'));
    }

    public function testSetGetCacheLogger(): void
    {
        $logger = $this->createMock(CacheLogger::class);

        $this->assertNull($this->config->getCacheLogger());

        $this->config->setCacheLogger($logger);

        $this->assertEquals($logger, $this->config->getCacheLogger());
    }

    public function testSetGetCacheFactory(): void
    {
        $factory = $this->createMock(CacheFactory::class);

        $this->assertNull($this->config->getCacheFactory());

        $this->config->setCacheFactory($factory);

        $this->assertEquals($factory, $this->config->getCacheFactory());
    }

    public function testSetGetQueryValidator(): void
    {
        $factory = $this->createMock(CacheFactory::class);
        $factory->method('getTimestampRegion')->willReturn($this->createMock(TimestampRegion::class));

        $this->config->setCacheFactory($factory);

        $validator = $this->createMock(QueryCacheValidator::class);

        $this->assertInstanceOf(TimestampQueryCacheValidator::class, $this->config->getQueryValidator());

        $this->config->setQueryValidator($validator);

        $this->assertEquals($validator, $this->config->getQueryValidator());
    }
}
