<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * A resolver is used to instantiate an entity listener.
 */
interface EntityListenerResolver
{
    /**
     * Clear all instances from the set, or a specific instance when given its identifier.
     *
     * @param string|null $className May be any arbitrary string. Name kept for BC only.
     */
    public function clear(string|null $className = null): void;

    /**
     * Returns a entity listener instance for the given identifier.
     *
     * @param string $className May be any arbitrary string. Name kept for BC only.
     */
    public function resolve(string $className): object;

    /**
     * Register a entity listener instance.
     */
    public function register(object $object): void;
}
