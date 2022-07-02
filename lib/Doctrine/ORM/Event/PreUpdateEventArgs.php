<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

/**
 * Class that holds event arguments for a preInsert/preUpdate event.
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /** @var array<string, array{mixed, mixed}|PersistentCollection> */
    private $entityChangeSet;

    /**
     * @param object    $entity
     * @param mixed[][] $changeSet
     * @psalm-param array<string, array{mixed, mixed}|PersistentCollection> $changeSet
     */
    public function __construct($entity, EntityManagerInterface $em, array &$changeSet)
    {
        parent::__construct($entity, $em);

        $this->entityChangeSet = &$changeSet;
    }

    /**
     * Retrieves entity changeset.
     *
     * @return mixed[][]
     * @psalm-return array<string, array{mixed, mixed}|PersistentCollection>
     */
    public function getEntityChangeSet()
    {
        return $this->entityChangeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasChangedField($field)
    {
        return isset($this->entityChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->entityChangeSet[$field][1] = $value;
    }

    /**
     * @deprecated 2.13. Use {@see getObject} instead.
     *
     * @return object
     */
    public function getEntity()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use getObjectManager() instead.',
            __METHOD__
        );

        return parent::getEntity();
    }

    /**
     * @deprecated 2.13. Use {@see getObjectManager} instead.
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/9875',
            'Method %s() is deprecated and will be removed in Doctrine ORM 3.0. Use getObjectManager() instead.',
            __METHOD__
        );

        return parent::getEntityManager();
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @throws InvalidArgumentException
     */
    private function assertValidField(string $field): void
    {
        if (! isset($this->entityChangeSet[$field])) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity "%s" in PreUpdateEventArgs.',
                $field,
                get_debug_type($this->getObject())
            ));
        }
    }
}
