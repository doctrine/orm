<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Driver\AttributesDriver;

class AttributeDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        $paths = [];

        return new AttributesDriver($paths);
    }
}