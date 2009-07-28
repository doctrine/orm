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
use \Closure;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * Collections of entities represent only the associations (links) to those entities.
 * That means, if the collection is part of a many-many mapping and you remove
 * entities from the collection, only the links in the relation table are removed (on flush).
 * Similarly, if you remove entities from a collection that is part of a one-many
 * mapping this will only result in the nulling out of the foreign keys on flush.
 * If you want entities in a one-many collection to be removed when
 * they're removed from the collection, use deleteOrphans => true on the one-many
 * mapping.
 *
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since     2.0
 * @version   $Revision: 4930 $
 * @author    Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author    Roman Borschel <roman@code-factory.org>
 * @author    Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
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

    /** Whether the collection has already been initialized. */
    protected $_initialized = true;

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
                $this->_backRefFieldName = $targetClass->inverseMappings[$assoc->sourceFieldName]->sourceFieldName;
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

    public function setLazyInitialization()
    {
        $this->_initialized = false;
    }

    /**
     * INTERNAL:
     * Adds an element to a collection during hydration, if it not already set.
     * This control is performed to avoid infinite recursion during hydration
     * of bidirectional many-to-many collections.
     * 
     * @param mixed $element The element to add.
     */
    public function hydrateAdd($element)
    {
        if (parent::contains($element)) {
            return;
        }
        parent::add($element);
        if ($this->_backRefFieldName) {
            // Set back reference to owner
            if ($this->_association->isOneToMany()) {
                // OneToMany
                $this->_typeClass->reflFields[$this->_backRefFieldName]
                        ->setValue($element, $this->_owner);
            } else {
                // ManyToMany
                $otherCollection = $this->_typeClass->reflFields[$this->_backRefFieldName]
                        ->getValue($element);
                $otherCollection->hydrateAdd($this->_owner);
            }
        }
    }
    
    /**
     * INTERNAL:
     * Sets a keyed element in the collection during hydration.
     *
     * @param mixed $key The key to set.
     * $param mixed $value The element to set.
     */
    public function hydrateSet($key, $value)
    {
        parent::set($key, $value);
    }

    /**
     * Initializes the collection by loading its contents from the database.
     */
    private function _initialize()
    {
        if (!$this->_initialized) {
            $this->_association->load($this->_owner, $this, $this->_em);
            $this->_initialized = true;
        }
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
     * Marks this collection as changed/dirty.
     */
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
    
    /**
     * 
     * @param $bool
     * @return unknown_type
     */
    public function setInitialized($bool)
    {
        $this->_initialized = $bool;
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
        return array('_elements', '_initialized');
    }

    /**
     * Decorated Collection methods.
     */
    public function unwrap()
    {
        $this->_initialize();
        return parent::unwrap();
    }

    public function first()
    {
        $this->_initialize();
        return parent::first();
    }

    public function last()
    {
        $this->_initialize();
        return parent::last();
    }

    public function key()
    {
        $this->_initialize();
        return parent::key();
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
        $this->_initialize();
        $removed = parent::remove($key);
        if ($removed) {
            $this->_changed();
            if ($this->_association->isOneToMany() && $this->_association->orphanRemoval) {
                $this->_em->getUnitOfWork()->scheduleOrphanRemoval($removed);
            }
        }
        
        return $removed;
    }

    public function removeElement($element)
    {
        $this->_initialize();
        $result = parent::removeElement($element);
        $this->_changed();
        return $result;
    }

    public function offsetExists($offset)
    {
        $this->_initialize();
        return parent::offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        $this->_initialize();
        return parent::offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_initialize();
        $result = parent::offsetSet($offset, $value);
        $this->_changed();
        return $result;
    }

    public function offsetUnset($offset)
    {
        $this->_initialize();
        $result = parent::offsetUnset($offset);
        $this->_changed();
        return $result;
    }

    public function containsKey($key)
    {
        $this->_initialize();
        return parent::containsKey($key);
    }

    /**
     * Checks whether an element is contained in the collection.
     * This is an O(n) operation.
     */
    public function contains($element)
    {
        $this->_initialize();
        return parent::contains($element);
    }

    public function exists(Closure $p)
    {
        $this->_initialize();
        return parent::exists($p);
    }

    public function search($element)
    {
        $this->_initialize();
        return parent::search($element);
    }

    public function get($key)
    {
        $this->_initialize();
        return parent::get($key);
    }

    public function getKeys()
    {
        $this->_initialize();
        return parent::getKeys();
    }

    public function getElements()
    {
        $this->_initialize();
        return parent::getElements();
    }

    /**
     * @override
     */
    public function count()
    {
        $this->_initialize();
        return parent::count();
    }

    /**
     * When the collection is used as a Map this is like put(key,value)/add(key,value).
     * When the collection is used as a List this is like add(position,value).
     *
     * @param integer $key
     * @param mixed $value
     * @override
     */
    public function set($key, $value)
    {
        $this->_initialize();
        $result = parent::set($key, $value);
        $this->_changed();
        return $result;
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
        $this->_initialize();
        $result = parent::add($value);
        $this->_changed();
        return $result;
    }

    public function isEmpty()
    {
        $this->_initialize();
        return parent::isEmpty();
    }

    public function getIterator()
    {
        $this->_initialize();
        return parent::getIterator();
    }

    public function map(Closure $func)
    {
        $this->_initialize();
        $result = parent::map($func);
        $this->_changed();
        return $result;
    }

    public function filter(Closure $p)
    {
        $this->_initialize();
        return parent::filter($p);
    }

    public function forAll(Closure $p)
    {
        $this->_initialize();
        return parent::forAll($p);
    }

    public function partition(Closure $p)
    {
        $this->_initialize();
        return parent::partition($p);
    }

    /**
     * Clears the collection.
     */
    public function clear()
    {
        $this->_initialize();
        //TODO: Register collection as dirty with the UoW if necessary
        //TODO: If oneToMany() && shouldDeleteOrphan() delete entities
        /*if ($this->_association->isOneToMany() && $this->_association->shouldDeleteOrphans) {
        foreach ($this->_data as $entity) {
        $this->_em->remove($entity);
        }
        }*/
        $result = parent::clear();
        if ($this->_association->isOwningSide) {
            $this->_changed();
            $this->_em->getUnitOfWork()->scheduleCollectionDeletion($this);
        }
        return $result;
    }
}
