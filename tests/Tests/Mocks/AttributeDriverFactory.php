<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Composer\InstalledVersions;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\DirectoryFilesIterator;
use Doctrine\Persistence\Mapping\Driver\FilePathNameIterator;

use function version_compare;

final class AttributeDriverFactory
{
    /** @param list<string> $paths */
    public static function createAttributeDriver(array $paths = []): AttributeDriver
    {
        if (! self::isFilePathsSupported()) {
            return new AttributeDriver($paths, true);
        }

        $filePaths = new FilePathNameIterator(new DirectoryFilesIterator($paths));

        return new AttributeDriver($filePaths, true);
    }

    public static function isFilePathsSupported(): bool
    {
        return version_compare(InstalledVersions::getVersion('doctrine/persistence'), '4.1', '>=');
    }

    public static function pathFiles(string $path): FilePathNameIterator
    {
        return new FilePathNameIterator(new DirectoryFilesIterator([$path]));
    }
}
