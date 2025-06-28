<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\Deprecations\Deprecation;
use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Doctrine\Persistence\Proxy;

use function strrpos;
use function substr;

use const PHP_VERSION_ID;

/**
 * Class-related functionality for objects that might or not be proxy objects
 * at the moment.
 */
final class DefaultProxyClassNameResolver implements ProxyClassNameResolver
{
    public function resolveClassName(string $className): string
    {
        if (PHP_VERSION_ID >= 80400) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/12005',
                'Class "%s" is deprecated. Use native lazy objects instead.',
                self::class,
            );
        }

        $pos = strrpos($className, '\\' . Proxy::MARKER . '\\');

        if ($pos === false) {
            return $className;
        }

        return substr($className, $pos + Proxy::MARKER_LENGTH + 2);
    }

    /** @return class-string */
    public static function getClass(object $object): string
    {
        if (PHP_VERSION_ID >= 80400) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/12005',
                'Class "%s" is deprecated. Use native lazy objects instead.',
                self::class,
            );
        }

        return (new self())->resolveClassName($object::class);
    }
}
