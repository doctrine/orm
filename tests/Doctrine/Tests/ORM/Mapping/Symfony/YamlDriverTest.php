<?php

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use \Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\Tests\VerifyDeprecations;

/**
 * @group DDC-1418
 */
class YamlDriverTest extends AbstractDriverTest
{
    use VerifyDeprecations;

    protected function getFileExtension()
    {
        return '.orm.yml';
    }

    protected function getDriver(array $paths = [])
    {
        $driver = new SimplifiedYamlDriver(array_flip($paths));
        $this->expectDeprecationMessage('YAML mapping driver is deprecated and will be removed in Doctrine ORM 3.0, please migrate to annotation or XML driver.');

        return $driver;
    }
}
