<?php

namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs,
    Doctrine\ORM\EntityManager;

/**
 * Class that holds event arguments for a preInsert/preUpdate event.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since 2.0
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $_entityChangeSet;

    /**
     *
     * @param object $entity
     * @param EntityManager $em
     * @param array $changeSet
     */
    public function __construct($entity, $em, array &$changeSet)
    {
        parent::__construct($entity, $em);
        $this->_entityChangeSet = &$changeSet;
    }

    public function getEntityChangeSet()
    {
        return $this->_entityChangeSet;
    }

    /**
     * Field has a changeset?
     *
     * @return bool
     */
    public function hasChangedField($field)
    {
        return isset($this->_entityChangeSet[$field]);
    }

    /**
     * Get the old value of the changeset of the changed field.
     * 
     * @param  string $field
     * @return mixed
     */
    public function getOldValue($field)
    {
    	$this->_assertValidField($field);

        return $this->_entityChangeSet[$field][0];
    }

    /**
     * Get the new value of the changeset of the changed field.
     *
     * @param  string $field
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->_assertValidField($field);

        return $this->_entityChangeSet[$field][1];
    }

    /**
     * Set the new value of this field.
     * 
     * @param string $field
     * @param mixed $value
     */
    public function setNewValue($field, $value)
    {
        $this->_assertValidField($field);

        $this->_entityChangeSet[$field][1] = $value;
    }

    private function _assertValidField($field)
    {
    	if (!isset($this->_entityChangeSet[$field])) {
            throw new \InvalidArgumentException(
                "Field '".$field."' is not a valid field of the entity ".
                "'".get_class($this->getEntity())."' in PreInsertUpdateEventArgs."
            );
        }
    }
}

