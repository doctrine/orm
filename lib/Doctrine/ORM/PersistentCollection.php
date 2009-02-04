<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\ORM\Mapping\AssociationMapping;

/**
 * A persistent collection wrapper.
 * 
 * A PersistentCollection represents a collection of elements that have persistent state.
 * Collections of entities represent only the associations (links) to those entities.
 * That means, if the collection is part of a many-many mapping and you remove
 * entities from the collection, only the links in the xref table are removed (on flush).
 * Similarly, if you remove entities from a collection that is part of a one-many
 * mapping this will only result in the nulling out of the foreign keys on flush
 * (or removal of the links in the xref table if the one-many is mapped through an
 * xref table). If you want entities in a one-many collection to be removed when
 * they're removed from the collection, use deleteOrphans => true on the one-many
 * mapping.
 *
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since     2.0
 * @version   $Revision: 4930 $
 * @author    Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author    Roman Borschel <roman@code-factory.org>
 */
final class PersistentCollection extends \Doctrine\Common\Collections\Collection
{   
    /**
     * The base type of the collection.
     *
     * @var string
     */
    private $_entityBaseType;

    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array
     */
    private $_snapshot = array();

    /**
     * The entity that owns this collection.
     * 
     * @var object
     */
    private $_owner;

    /**
     * The association mapping the collection belongs to.
     * This is currently either a OneToManyMapping or a ManyToManyMapping.
     *
     * @var Doctrine\ORM\Mapping\AssociationMapping
     */
    private $_association;

    /**
     * The name of the field that is used for collection key mapping.
     *
     * @var string
     */
    private $_keyField;
    
    /**
     * The EntityManager that manages the persistence of the collection.
     *
     * @var Doctrine\ORM\EntityManager
     */
    private $_em;
    
    /**
     * The name of the field on the target entities that points to the owner
     * of the collection. This is only set if the association is bidirectional.
     *
     * @var string
     */
    private $_backRefFieldName;
    
    /**
     * Hydration flag.
     *
     * @var boolean
     * @see _setHydrationFlag()
     */
    private $_hydrationFlag;

    /**
     * The class descriptor of the owning entity.
     */
    private $_ownerClass;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    private $_isDirty = false;

    /**
     * Creates a new persistent collection.
     */
    public function __construct(EntityManager $em, $entityBaseType, array $data = array(), $keyField = null)
    {
        parent::__construct($data);
        $this->_entityBaseType = $entityBaseType;
        $this->_em = $em;
        $this->_ownerClass = $em->getClassMetadata($entityBaseType);
        if ($keyField !== null) {
            if ( ! $this->_ownerClass->hasField($keyField)) {
                throw new DoctrineException("Invalid field '$keyField' can't be used as key.");
            }
            $this->_keyField = $keyField;
        }
    }

    /**
     * INTERNAL: Sets the key column for this collection
     *
     * @param string $column
     * @return Doctrine_Collection
     */
    public function setKeyField($fieldName)
    {
        $this->_keyField = $fieldName;
        return $this;
    }

    /**
     * INTERNAL: returns the name of the key column
     *
     * @return string
     */
    public function getKeyField()
    {
        return $this->_keyField;
    }
    
    /**
     * INTERNAL:
     * Sets the collection owner. Used (only?) during hydration.
     *
     * @param object $entity
     * @param AssociationMapping $assoc
     */
    public function _setOwner($entity, AssociationMapping $assoc)
    {
        $this->_owner = $entity;
        $this->_association = $assoc;
        if ($assoc->isInverseSide()) {
            // for sure bidirectional
            $this->_backRefFieldName = $assoc->getMappedByFieldName();
        } else {
            $targetClass = $this->_em->getClassMetadata($assoc->getTargetEntityName());
            if ($targetClass->hasInverseAssociationMapping($assoc->getSourceFieldName())) {
                // bidirectional
                $this->_backRefFieldName = $targetClass->getInverseAssociationMapping(
                        $assoc->getSourceFieldName())->getSourceFieldName();
            }
        }
    }

    /**
     * INTERNAL:
     * Gets the collection owner.
     *
     * @return object
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Gets the class descriptor for the owning entity class.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getOwnerClass()
    {
        return $this->_ownerClass;
    }

    /**
     * Removes an element from the collection.
     *
     * @param mixed $key
     * @return boolean
     * @override
     */
    public function remove($key)
    {
        //TODO: Register collection as dirty with the UoW if necessary
        //$this->_em->getUnitOfWork()->scheduleCollectionUpdate($this);
        //TODO: delete entity if shouldDeleteOrphans
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans()) {
            $this->_em->delete($removed);
        }*/
        $removed = parent::remove($key);
        $this->_changed();
        return $removed;
    }

    /**
     * When the collection is a Map this is like put(key,value)/add(key,value).
     * When the collection is a List this is like add(position,value).
     * 
     * @param integer $key
     * @param mixed $value
     * @override
     */
    public function set($key, $value)
    {
        parent::set($key, $value);
        //TODO: Register collection as dirty with the UoW if necessary
        if ( ! $this->_hydrationFlag) $this->_changed();
    }

    /**
     * Adds an element to the collection.
     * 
     * @param mixed $value
     * @param string $key 
     * @return TRUE
     * @override
     */
    public function add($value)
    {
        $result = parent::add($value);
        if ( ! $result) return $result; // EARLY EXIT

        if ($this->_hydrationFlag) {
            if ($this->_backRefFieldName) {
                // set back reference to owner
                $this->_ownerClass->getReflectionProperty($this->_backRefFieldName)
                        ->setValue($value, $this->_owner);
            }
        } else {
            //TODO: Register collection as dirty with the UoW if necessary
            $this->_changed();
        }
        
        return true;
    }
    
    /**
     * Adds all entities of the other collection to this collection.
     *
     * @param object $otherCollection
     * @todo Impl
     * @override
     */
    public function addAll($otherCollection)
    {
        parent::addAll($otherCollection);
        //...
        //TODO: Register collection as dirty with the UoW if necessary
        //$this->_changed();
    }
    
    /**
     * INTERNAL:
     * Sets a flag that indicates whether the collection is currently being hydrated.
     * This has the following consequences:
     * 1) During hydration, bidirectional associations are completed automatically
     *    by setting the back reference.
     * 2) During hydration no change notifications are reported to the UnitOfWork.
     *    I.e. that means add() etc. do not cause the collection to be scheduled
     *    for an update.
     *
     * @param boolean $bool
     */
    public function _setHydrationFlag($bool)
    {
        $this->_hydrationFlag = $bool;
    }

    /**
     * INTERNAL: Takes a snapshot from this collection.
     *
     * Snapshots are used for diff processing, for example
     * when a fetched collection has three elements, then two of those
     * are being removed the diff would contain one element.
     */
    public function _takeSnapshot()
    {
        $this->_snapshot = $this->_data;
    }

    /**
     * INTERNAL:
     * Returns the last snapshot of the elements in the collection.
     *
     * @return array The last snapshot of the elements.
     */
    public function _getSnapshot()
    {
        return $this->_snapshot;
    }

    /**
     * INTERNAL:
     * getDeleteDiff
     *
     * @return array
     */
    public function getDeleteDiff()
    {
        return array_udiff($this->_snapshot, $this->_data, array($this, '_compareRecords'));
    }

    /**
     * INTERNAL getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff($this->_data, $this->_snapshot, array($this, '_compareRecords'));
    }

    /**
     * Compares two records. To be used on _snapshot diffs using array_udiff.
     * 
     * @return integer
     */
    private function _compareRecords($a, $b)
    {
        if ($a === $b) {
            return 0;
        }
        return 1;
    }
    
    /**
     * INTERNAL: Gets the association mapping of the collection.
     * 
     * @return Doctrine\ORM\Mapping\AssociationMapping
     */
    public function getMapping()
    {
        return $this->_association;
    }
    
    /**
     * Clears the collection.
     */
    public function clear()
    {
        //TODO: Register collection as dirty with the UoW if necessary
        //TODO: If oneToMany() && shouldDeleteOrphan() delete entities
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans()) {
            foreach ($this->_data as $entity) {
                $this->_em->delete($entity);
            }
        }*/
        parent::clear();
    }
    
    private function _changed()
    {
        $this->_isDirty = true;
        /*if ( ! $this->_em->getUnitOfWork()->isCollectionScheduledForUpdate($this)) {
            //var_dump(get_class($this->_snapshot[0]));
            //echo "NOT!";
            //$this->_em->getUnitOfWork()->scheduleCollectionUpdate($this);
        }*/
    }
    
    public function isDirty()
    {
        return $this->_isDirty;
    }
    
    public function setDirty($dirty)
    {
        $this->_isDirty = $dirty;
    }
}
