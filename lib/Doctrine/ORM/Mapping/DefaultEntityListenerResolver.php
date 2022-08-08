<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use function get_class;
use function trim;

/**
 * The default DefaultEntityListener
 */
class DefaultEntityListenerResolver implements EntityListenerResolver
{
    /** @psalm-var array<class-string, object> Map to store entity listener instances. */
    private array $instances = [];

    public function clear(?string $className = null): void
    {
        if ($className === null) {
            $this->instances = [];

            return;
        }

        $className = trim($className, '\\');
        unset($this->instances[$className]);
    }

    public function register(object $object): void
    {
        $this->instances[get_class($object)] = $object;
    }

    public function resolve(string $className): object
    {
        $className = trim($className, '\\');

        return $this->instances[$className] ??= new $className();
    }
}
