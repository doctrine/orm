<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Symfony;

use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Doctrine\Persistence\Mapping\Driver\FileDriver;

use function array_flip;

/** @group DDC-1418 */
class YamlDriverTest extends DriverTestCase
{
    protected function getFileExtension(): string
    {
        return '.orm.yml';
    }

    protected function getDriver(array $paths = []): FileDriver
    {
        return new SimplifiedYamlDriver(array_flip($paths));
    }
}
