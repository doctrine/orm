<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

/**
 * An OptimisticLockFailed is thrown when a version check on an object
 * that uses optimistic locking through a version field fails.
 */
final class OptimisticLockFailed extends \RuntimeException implements ORMException
{
    /** @var object|null */
    private $entity;

    /**
     * @param string $msg
     * @param object $entity
     */
    public function __construct($msg, $entity)
    {
        parent::__construct($msg);
        $this->entity = $entity;
    }

    /**
     * Gets the entity that caused the exception.
     *
     * @return object|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object $entity
     * @return OptimisticLockFailed
     */
    public static function lockFailed($entity)
    {
        return new self('The optimistic lock on an entity failed.', $entity);
    }

    /**
     * @param object $entity
     * @param int    $expectedLockVersion
     * @param int    $actualLockVersion
     * @return OptimisticLockFailed
     */
    public static function lockFailedVersionMismatch($entity, $expectedLockVersion, $actualLockVersion)
    {
        $expectedLockVersion = ($expectedLockVersion instanceof \DateTime) ? $expectedLockVersion->getTimestamp() : $expectedLockVersion;
        $actualLockVersion   = ($actualLockVersion instanceof \DateTime) ? $actualLockVersion->getTimestamp() : $actualLockVersion;

        return new self('The optimistic lock failed, version ' . $expectedLockVersion . ' was expected, but is actually ' . $actualLockVersion, $entity);
    }

    /**
     * @param string $entityName
     *
     * @return OptimisticLockFailed
     */
    public static function notVersioned($entityName)
    {
        return new self('Cannot obtain optimistic lock on unversioned entity ' . $entityName, null);
    }
}
