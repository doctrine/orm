<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Class that holds event arguments for a preInsert/preUpdate event.
 *
 * @author Guilherme Blanco <guilehrmeblanco@hotmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since  2.0
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $entityChangeSet;

    /**
     * Constructor.
     *
     * @param object                 $entity
     * @param EntityManagerInterface $em
     * @param array                  $changeSet
     */
    public function __construct($entity, EntityManagerInterface $em, array &$changeSet)
    {
        parent::__construct($entity, $em);

        $this->entityChangeSet = &$changeSet;
    }

    /**
     * Retrieves entity changeset.
     *
     * @return array
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
     * @return boolean
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
     * Asserts the field exists in changeset.
     *
     * @param string $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidField($field)
    {
        if ( ! isset($this->entityChangeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getEntity())
            ));
        }
    }
}
