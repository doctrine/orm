<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\ClassLocator;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;

use function interface_exists;

final class AttributeDriverFactory
{
    /** @param list<string> $paths */
    public static function createAttributeDriver(array $paths = []): AttributeDriver
    {
        if (! self::isClassLocatorSupported()) {
            // Persistence < 4.1
            return new AttributeDriver($paths);
        }

        // Persistence >= 4.1
        $classLocator = FileClassLocator::createFromDirectories($paths);

        return new AttributeDriver($classLocator);
    }

    /** Supported since doctrine/persistence >= 4.1 */
    public static function isClassLocatorSupported(): bool
    {
        return interface_exists(ClassLocator::class);
    }
}
