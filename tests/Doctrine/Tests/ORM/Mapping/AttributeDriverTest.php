<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Driver\AttributesDriver;

class AttributeDriverTest extends AbstractMappingDriverTest
{
    /** @before */
    public function requiresPhp8Assertion()
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('requies PHP 8.0');
        }
    }

    protected function _loadDriver()
    {
        $paths = [];

        return new AttributesDriver($paths);
    }

    public function testNamedQuery()
    {
        $this->markTestSkipped('AttributeDriver does not support named queries.');
    }

    public function testNamedNativeQuery()
    {
        $this->markTestSkipped('AttributeDriver does not support named native queries.');
    }

    public function testSqlResultSetMapping()
    {
        $this->markTestSkipped('AttributeDriver does not support named sql resultset mapping.');
    }

    public function testAssociationOverridesMapping()
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testInversedByOverrideMapping()
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testFetchOverrideMapping()
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }

    public function testAttributeOverridesMapping()
    {
        $this->markTestSkipped('AttributeDriver does not support association overrides.');
    }
}