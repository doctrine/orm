<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Tests\VerifyDeprecations;

use function array_flip;

/**
 * @group DDC-1418
 */
class YamlDriverTest extends AbstractDriverTest
{
    use VerifyDeprecations;

    protected function getFileExtension(): string
    {
        return '.orm.yml';
    }

    protected function getDriver(array $paths = []): FileDriver
    {
        $driver = new SimplifiedYamlDriver(array_flip($paths));
        $this->expectDeprecationMessageSame('YAML mapping driver is deprecated and will be removed in Doctrine ORM 3.0, please migrate to annotation or XML driver.');

        return $driver;
    }
}
