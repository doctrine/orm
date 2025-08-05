<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Composer\InstalledVersions;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use LogicException;

use function array_map;
use function array_merge;
use function assert;
use function glob;
use function realpath;
use function version_compare;

use const GLOB_BRACE;

final class AttributeDriverFactory
{
    /** @param list<string> $paths */
    public static function createAttributeDriver(array $paths = []): AttributeDriver
    {
        if (! self::isPathsAsSourceFilePathNamesSupported()) {
            return new AttributeDriver($paths, true, false);
        }

        $sourceFilePathNames = array_merge(...array_map(self::pathFiles(...), $paths));

        return new AttributeDriver($sourceFilePathNames, true, true);
    }

    public static function isPathsAsSourceFilePathNamesSupported(): bool
    {
        return version_compare(InstalledVersions::getVersion('doctrine/persistence'), '4.1', '>=');
    }

    /** @return list<string> */
    public static function pathFiles(string $path): array
    {
        $realpath = realpath($path);

        assert($realpath !== false);

        return glob($realpath . '/{,*/}*.php', GLOB_BRACE)
            ?: throw new LogicException('Could not load driver files');
    }
}
