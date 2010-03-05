<?php

namespace Doctrine\ORM;

/**
 * Base exception class for all ORM exceptions.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ORMException extends \Exception
{
    public static function entityMissingAssignedId($entity)
    {
        return new self("Entity of type " . get_class($entity) . " is missing an assigned ID.");
    }

    public static function unrecognizedField($field)
    {
        return new self("Unrecognized field: $field");
    }

    public static function removedEntityInCollectionDetected($entity, $assoc)
    {
        return new self("Removed entity of type " . get_class($entity)
                . " detected in collection '" . $assoc->sourceFieldName . "' during flush."
                . " Remove deleted entities from collections.");
    }

    public static function invalidEntityState($state)
    {
        return new self("Invalid entity state: $state.");
    }

    public static function detachedEntityCannotBeRemoved()
    {
        return new self("A detached entity can not be removed.");
    }

    public static function invalidFlushMode($mode)
    {
        return new self("'$mode' is an invalid flush mode.");
    }

    public static function entityManagerClosed()
    {
        return new self("The EntityManager is closed.");
    }

    public static function invalidHydrationMode($mode)
    {
        return new self("'$mode' is an invalid hydration mode.");
    }

    public static function mismatchedEventManager()
    {
        return new self("Cannot use different EventManager instances for EntityManager and Connection.");
    }

    public static function findByRequiresParameter($methodName)
    {
        return new self("You need to pass a parameter to '".$methodName."'");
    }

    public static function invalidFindByCall($entityName, $fieldName, $method)
    {
        return new self(
            "Entity '".$entityName."' has no field '".$fieldName."'. ".
            "You can therefore not call '".$method."' on the entities' repository"
        );
    }

    public static function invalidResultCacheDriver() {
        return new self("Invalid result cache driver; it must implement \Doctrine\Common\Cache\Cache.");
    }

    public static function notSupported() {
        return new self("This behaviour is (currently) not supported by Doctrine 2");
    }

    public static function queryCacheNotConfigured()
    {
        return new self('Query Cache is not configured.');
    }

    public static function metadataCacheNotConfigured()
    {
        return new self('Class Metadata Cache is not configured.');
    }

    public static function proxyClassesAlwaysRegenerating()
    {
        return new self('Proxy Classes are always regenerating.');
    }

    public static function unknownEntityNamespace($entityNamespaceAlias)
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }
}
