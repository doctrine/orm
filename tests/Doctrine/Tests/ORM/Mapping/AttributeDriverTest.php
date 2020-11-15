<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Driver\AttributesDriver;

class AttributeDriverTest extends AbstractMappingDriverTest
{
    /** @before */
    public function requiresPhp8Assertion()
    {
        if (PHP_VERSION_ID <= 80000) {
            $this->markTestSkipped('requies PHP 8.0');
        }
    }

    protected function _loadDriver()
    {
        $paths = [];

        return new AttributesDriver($paths);
    }
}