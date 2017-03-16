<?php

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use \Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;

/**
 * @group DDC-1418
 */
class YamlDriverTest extends AbstractDriverTest
{
    protected function getFileExtension()
    {
        return '.orm.yml';
    }

    protected function getDriver(array $paths = [])
    {
        $driver = new SimplifiedYamlDriver(array_flip($paths));

        return $driver;
    }
}
