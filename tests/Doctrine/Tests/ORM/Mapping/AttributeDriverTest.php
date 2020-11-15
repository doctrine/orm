<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Driver\AttributesDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use const PHP_VERSION_ID;

class AttributeDriverTest extends AbstractMappingDriverTest
{
    /** @before */
    public function requiresPhp8Assertion(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('requies PHP 8.0');
        }
    }

    protected function loadDriver(): MappingDriver
    {
        $paths = [];

        return new AttributesDriver($paths);
    }

    public function testNamedQuery(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named queries.');
    }

    public function testNamedNativeQuery(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named native queries.');
    }

    public function testSqlResultSetMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support named sql resultset mapping.');
    }

    public function testAssociationOverridesMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testInversedByOverrideMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testFetchOverrideMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testAttributeOverridesMapping(): void
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }
}
