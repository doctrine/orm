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
    public static function invalidResultCacheDriver()
    {
        return new self('Invalid result cache driver; it must implement Doctrine\\Common\\Cache\\Cache.');
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
     * @param string $className
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
