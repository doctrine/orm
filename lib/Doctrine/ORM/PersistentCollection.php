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

use Doctrine\Common\DoctrineException;
use Doctrine\ORM\Mapping\AssociationMapping;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 * Collections of entities represent only the associations (links) to those entities.
 * That means, if the collection is part of a many-many mapping and you remove
 * entities from the collection, only the links in the relation table are removed (on flush).
 * Similarly, if you remove entities from a collection that is part of a one-many
 * mapping this will only result in the nulling out of the foreign keys on flush
 * (or removal of the links in the relation table if the one-many is mapped through a
 * relation table). If you want entities in a one-many collection to be removed when
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
    private $_type;

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
     * The name of the field that is used for collection indexing.
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
     * of the collection. This is only set if the association is bi-directional.
     *
     * @var string
     */
    private $_backRefFieldName;
    
    /**
     * Hydration flag.
     *
     * @var boolean
     * @see setHydrationFlag()
     */
    private $_hydrationFlag = false;

    /**
     * The class descriptor of the owning entity.
     */
    private $_typeClass;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    private $_isDirty = false;

    private $_initialized = false;

    /**
     * Creates a new persistent collection.
     */
    public function __construct(EntityManager $em, $class, array $data = array())
    {
        parent::__construct($data);
        $this->_type = $class->name;
        $this->_em = $em;
        $this->_typeClass = $class;
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
    public function setOwner($entity, AssociationMapping $assoc)
    {
        $this->_owner = $entity;
        $this->_association = $assoc;
        if ($assoc->isInverseSide()) {
            // For sure bi-directional
            $this->_backRefFieldName = $assoc->mappedByFieldName;
        } else {
            $targetClass = $this->_em->getClassMetadata($assoc->targetEntityName);
            if (isset($targetClass->inverseMappings[$assoc->sourceFieldName])) {
                // Bi-directional
                $this->_backRefFieldName = $targetClass->inverseMappings[$assoc->sourceFieldName]
                        ->sourceFieldName;
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
        return $this->_typeClass;
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
        //TODO: delete entity if shouldDeleteOrphans
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans()) {
            $this->_em->delete($removed);
        }*/
        $removed = parent::remove($key);
        if ($removed) {
            $this->_changed();
        }
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
        if ( ! $this->_hydrationFlag) {
            $this->_changed();
        }
    }

    /**
     * Adds an element to the collection.
     * 
     * @param mixed $value
     * @param string $key 
     * @return boolean Always TRUE.
     * @override
     */
    public function add($value)
    {
        parent::add($value);

        if ($this->_hydrationFlag) {
            if ($this->_backRefFieldName) {
                // Set back reference to owner
                if ($this->_association->isOneToMany()) {
                    $this->_typeClass->getReflectionProperty($this->_backRefFieldName)
                            ->setValue($value, $this->_owner);
                } else {
                    // ManyToMany
                    $this->_typeClass->getReflectionProperty($this->_backRefFieldName)
                            ->getValue($value)->add($this->_owner);
                }
            }
        } else {
            $this->_changed();
        }
        
        return true;
    }
    
    /**
     * Adds all elements of the other collection to this collection.
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
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation.
     */
    public function contains($element)
    {
        
        if ( ! $this->_initialized) {
            //TODO: Probably need to hit the database here...?
            //return $this->_checkElementExistence($element);
        }
        return parent::contains($element);
    }

    /**
     * @override
     */
    public function count()
    {
        if ( ! $this->_initialized) {
            //TODO: Initialize
        }
        return parent::count();
    }

    private function _checkElementExistence($element)
    {
        
    }

    private function _initialize()
    {
        
    }
    
    /**
     * INTERNAL:
     * Sets a flag that indicates whether the collection is currently being hydrated.
     *
     * If the flag is set to TRUE, this has the following consequences:
     * 
     * 1) During hydration, bidirectional associations are completed automatically
     *    by setting the back reference.
     * 2) During hydration no change notifications are reported to the UnitOfWork.
     *    That means add() etc. do not cause the collection to be scheduled
     *    for an update.
     *
     * @param boolean $bool
     */
    public function setHydrationFlag($bool)
    {
        $this->_hydrationFlag = $bool;
    }

    /**
     * INTERNAL:
     * Tells this collection to take a snapshot of its current state.
     */
    public function takeSnapshot()
    {
        $this->_snapshot = $this->_elements;
    }

    /**
     * INTERNAL:
     * Returns the last snapshot of the elements in the collection.
     *
     * @return array The last snapshot of the elements.
     */
    public function getSnapshot()
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
        return array_udiff($this->_snapshot, $this->_elements, array($this, '_compareRecords'));
    }

    /**
     * INTERNAL getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff($this->_elements, $this->_snapshot, array($this, '_compareRecords'));
    }

    /**
     * Compares two records. To be used on _snapshot diffs using array_udiff.
     * 
     * @return integer
     */
    private function _compareRecords($a, $b)
    {
        return $a === $b ? 0 : 1;
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
        $this->_changed();
    }
    
    private function _changed()
    {
        $this->_isDirty = true;
    }

    /**
     * Gets a boolean flag indicating whether this colleciton is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return boolean TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty()
    {
        return $this->_isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param boolean $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty)
    {
        $this->_isDirty = $dirty;
    }

    /* Serializable implementation */

    /**
     * Called by PHP when this collection is serialized. Ensures that only the
     * elements are properly serialized.
     *
     * @internal Tried to implement Serializable first but that did not work well
     *           with circular references. This solution seems simpler and works well.
     */
    public function __sleep()
    {
        return array('_elements');
    }
}