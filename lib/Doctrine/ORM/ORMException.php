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
     * @return ORMException
     */
    public static function notSupported()
    {
        return new self('This behaviour is (currently) not supported by Doctrine 2');
    }
}
