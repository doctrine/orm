<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\Common\Cache\Cache as CacheDriver;
use Exception;
use function get_class;
use function implode;
use function sprintf;

/**
 * Base exception class for all ORM exceptions.
 */
class ORMException extends Exception
{
    /**
     * @return ORMException
     */
    public static function missingMappingDriverImpl()
    {
        return new self("It's a requirement to specify a Metadata Driver and pass it " .
            'to Doctrine\\ORM\\Configuration::setMetadataDriverImpl().');
    }

    /**
     * @param string $field
     *
     * @return ORMException
     */
    public static function unrecognizedField($field)
    {
        return new self('Unrecognized field: ' . $field);
    }

    /**
     * @param string $class
     * @param string $association
     * @param string $given
     * @param string $expected
     *
     * @return ORMInvalidArgumentException
     */
    public static function unexpectedAssociationValue($class, $association, $given, $expected)
    {
        return new self(sprintf('Found entity of type %s on association %s#%s, but expecting %s', $given, $class, $association, $expected));
    }

    /**
     * @param string $className
     * @param string $field
     *
     * @return ORMException
     */
    public static function invalidOrientation($className, $field)
    {
        return new self('Invalid order by orientation specified for ' . $className . '#' . $field);
    }

    /**
     * @return ORMException
     */
    public static function entityManagerClosed()
    {
        return new self('The EntityManager is closed.');
    }

    /**
     * @param string $mode
     *
     * @return ORMException
     */
    public static function invalidHydrationMode($mode)
    {
        return new self(sprintf("'%s' is an invalid hydration mode.", $mode));
    }

    /**
     * @return ORMException
     */
    public static function mismatchedEventManager()
    {
        return new self('Cannot use different EventManager instances for EntityManager and Connection.');
    }

    /**
     * @param string $methodName
     *
     * @return ORMException
     */
    public static function findByRequiresParameter($methodName)
    {
        return new self("You need to pass a parameter to '" . $methodName . "'");
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @param string $method
     *
     * @return ORMException
     */
    public static function invalidMagicCall($entityName, $fieldName, $method)
    {
        return new self(
            "Entity '" . $entityName . "' has no field '" . $fieldName . "'. " .
            "You can therefore not call '" . $method . "' on the entities' repository"
        );
    }

    /**
     * @param string $entityName
     * @param string $associationFieldName
     *
     * @return ORMException
     */
    public static function invalidFindByInverseAssociation($entityName, $associationFieldName)
    {
        return new self(
            "You cannot search for the association field '" . $entityName . '#' . $associationFieldName . "', " .
            'because it is the inverse side of an association. Find methods only work on owning side associations.'
        );
    }

    /**
     * @return ORMException
     */
    public static function notSupported()
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }

    /**
     * @return ORMException
     */
    public static function queryCacheNotConfigured()
    {
        return new self('Query Cache is not configured.');
    }

    /**
     * @return ORMException
     */
    public static function metadataCacheNotConfigured()
    {
        return new self('Class Metadata Cache is not configured.');
    }

    /**
     * @return ORMException
     */
    public static function queryCacheUsesNonPersistentCache(CacheDriver $cache)
    {
        return new self('Query Cache uses a non-persistent cache driver, ' . get_class($cache) . '.');
    }

    /**
     * @return ORMException
     */
    public static function metadataCacheUsesNonPersistentCache(CacheDriver $cache)
    {
        return new self('Metadata Cache uses a non-persistent cache driver, ' . get_class($cache) . '.');
    }

    /**
     * @return ORMException
     */
    public static function proxyClassesAlwaysRegenerating()
    {
        return new self('Proxy Classes are always regenerating.');
    }

    /**
     * @param string $className
     *
     * @return ORMException
     */
    public static function invalidEntityRepository($className)
    {
        return new self("Invalid repository class '" . $className . "'. It must be a Doctrine\Common\Persistence\ObjectRepository.");
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return ORMException
     */
    public static function missingIdentifierField($className, $fieldName)
    {
        return new self(sprintf('The identifier %s is missing for a query of %s', $fieldName, $className));
    }

    /**
     * @param string   $className
     * @param string[] $fieldNames
     *
     * @return ORMException
     */
    public static function unrecognizedIdentifierFields($className, $fieldNames)
    {
        return new self(
            "Unrecognized identifier fields: '" . implode("', '", $fieldNames) . "' " .
            "are not present on class '" . $className . "'."
        );
    }

    /**
     * @return ORMException
     */
    public static function cantUseInOperatorOnCompositeKeys()
    {
        return new self("Can't use IN operator on entities that have composite keys.");
    }
}
