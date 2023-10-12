<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\Persistence\Mapping\ProxyClassNameResolver;
use Doctrine\Persistence\Proxy;

use function strrpos;
use function substr;

/**
 * Class-related functionality for objects that might or not be proxy objects
 * at the moment.
 *
 * @internal
 */
class DefaultProxyClassNameResolver implements ProxyClassNameResolver
{
    public function resolveClassName(string $className): string
    {
        $pos = strrpos($className, '\\' . Proxy::MARKER . '\\');

        if ($pos === false) {
            return $className;
        }

        return substr($className, $pos + Proxy::MARKER_LENGTH + 2);
    }
}
