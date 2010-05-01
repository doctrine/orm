<?php
/*
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

use Doctrine\ORM\Mapping\AssociationMapping,
    Doctrine\Common\Collections\Collection,
    Closure;

/**
 * A PersistentCollection represents a collection of elements that have persistent state.
 *
 * Collections of entities represent only the associations (links) to those entities.
 * That means, if the collection is part of a many-many mapping and you remove
 * entities from the collection, only the links in the relation table are removed (on flush).
 * Similarly, if you remove entities from a collection that is part of a one-many
 * mapping this will only result in the nulling out of the foreign keys on flush.
 *
 * @license   http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since     2.0
 * @version   $Revision: 4930 $
 * @author    Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author    Roman Borschel <roman@code-factory.org>
 * @author    Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 */
final class PersistentCollection implements Collection
{
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
     * The class descriptor of the collection's entity type.
     */
    private $_typeClass;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    private $_isDirty = false;

    /**
     * Whether the collection has already been initialized.
     * 
     * @var boolean
     */
    private $_initialized = true;
    
    /**
     * The wrapped Collection instance.
     * 
     * @var Collection
     */
    private $_coll;

    /**
     * Creates a new persistent collection.
     * 
     * @param EntityManager $em The EntityManager the collection will be associated with.
     * @param ClassMetadata $class The class descriptor of the entity type of this collection.
     * @param array The collection elements.
     */
    public function __construct(EntityManager $em, $class, $coll)
    {
        $this->_coll = $coll;
        $this->_em = $em;
        $this->_typeClass = $class;
    }

    /**
     * INTERNAL:
     * Sets the collection's owning entity together with the AssociationMapping that
     * describes the association between the owner and the elements of the collection.
     *
     * @param object $entity
     * @param AssociationMapping $assoc
     */
    public function setOwner($entity, AssociationMapping $assoc)
    {
        $this->_owner = $entity;
        $this->_association = $assoc;
        $this->_backRefFieldName = $assoc->inversedBy ?: $assoc->mappedBy;
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
    
    public function getTypeClass()
    {
        return $this->_typeClass;
    }

    /**
     * INTERNAL:
     * Adds an element to a collection during hydration. This will automatically
     * complete bidirectional associations in the case of a one-to-many association.
     * 
     * @param mixed $element The element to add.
     */
    public function hydrateAdd($element)
    {
        $this->_coll->add($element);
        // If _backRefFieldName is set and its a one-to-many association,
        // we need to set the back reference.
        if ($this->_backRefFieldName && $this->_association->isOneToMany()) {
            // Set back reference to owner
            $this->_typeClass->reflFields[$this->_backRefFieldName]
                    ->setValue($element, $this->_owner);
            $this->_em->getUnitOfWork()->setOriginalEntityProperty(
                    spl_object_hash($element),
                    $this->_backRefFieldName,
                    $this->_owner);
        }
    }
    
    /**
     * INTERNAL:
     * Sets a keyed element in the collection during hydration.
     *
     * @param mixed $key The key to set.
     * $param mixed $value The element to set.
     */
    public function hydrateSet($key, $element)
    {
        $this->_coll->set($key, $element);
        // If _backRefFieldName is set, then the association is bidirectional
        // and we need to set the back reference.
        if ($this->_backRefFieldName && $this->_association->isOneToMany()) {
            // Set back reference to owner
            $this->_typeClass->reflFields[$this->_backRefFieldName]
                    ->setValue($element, $this->_owner);
        }
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    private function _initialize()
    {
        if ( ! $this->_initialized) {
            if ($this->_isDirty) {
                // Has NEW objects added through add(). Remember them.
                $newObjects = $this->_coll->toArray();
            }
            $this->_coll->clear();
            $this->_association->load($this->_owner, $this, $this->_em);
            $this->takeSnapshot();
            // Reattach NEW objects added through add(), if any.
            if (isset($newObjects)) {
                foreach ($newObjects as $obj) {
                    $this->_coll->add($obj);
                }
                $this->_isDirty = true;
            }
            $this->_initialized = true;
        }
    }

    /**
     * INTERNAL:
     * Tells this collection to take a snapshot of its current state.
     */
    public function takeSnapshot()
    {
        $this->_snapshot = $this->_coll->toArray();
        $this->_isDirty = false;
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
        return array_udiff_assoc($this->_snapshot, $this->_coll->toArray(),
                function($a, $b) {return $a === $b ? 0 : 1;});
    }

    /**
     * INTERNAL:
     * getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff()
    {
        return array_udiff_assoc($this->_coll->toArray(), $this->_snapshot,
                function($a, $b) {return $a === $b ? 0 : 1;});
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
        if ( ! $this->_isDirty) {
            $this->_isDirty = true;
            //if ($this->_isNotifyRequired) {
                //$this->_em->getUnitOfWork()->scheduleCollectionUpdate($this);
            //}
        }
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
     * Sets the initialized flag of the collection, forcing it into that state.
     * 
     * @param boolean $bool
     */
    public function setInitialized($bool)
    {
        $this->_initialized = $bool;
    }
    
    /**
     * Checks whether this collection has been initialized.
     *
     * @return boolean
     */
    public function isInitialized()
    {
        return $this->_initialized;
    }

    /** {@inheritdoc} */
    public function first()
    {
        $this->_initialize();
        return $this->_coll->first();
    }

    /** {@inheritdoc} */
    public function last()
    {
        $this->_initialize();
        return $this->_coll->last();
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        // TODO: If the keys are persistent as well (not yet implemented)
        //       and the collection is not initialized and orphanRemoval is
        //       not used we can issue a straight SQL delete/update on the
        //       association (table). Without initializing the collection.
        $this->_initialize();
        $removed = $this->_coll->remove($key);
        if ($removed) {
            $this->_changed();
            if ($this->_association !== null && $this->_association->isOneToMany() &&
                    $this->_association->orphanRemoval) {
                $this->_em->getUnitOfWork()->scheduleOrphanRemoval($removed);
            }
        }

        return $removed;
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element)
    {
        // TODO: Assuming the identity of entities in a collection is always based
        //       on their primary key (there is no equals/hashCode in PHP),
        //       if the collection is not initialized, we could issue a straight
        //       SQL DELETE/UPDATE on the association (table) without initializing
        //       the collection.
        /*if ( ! $this->_initialized) {
            $this->_em->getUnitOfWork()->getCollectionPersister($this->_association)
                ->deleteRows($this, $element);
        }*/
        
        $this->_initialize();
        $result = $this->_coll->removeElement($element);
        $this->_changed();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key)
    {
        $this->_initialize();
        return $this->_coll->containsKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element)
    {
        /* DRAFT
        if ($this->_initialized) {
            return $this->_coll->contains($element);
        } else {
            if ($element is MANAGED) {
                if ($this->_coll->contains($element)) {
                    return true;
                }
                $exists = check db for existence;
                if ($exists) {
                    $this->_coll->add($element);
                }
                return $exists;
            }
            return false;
        }*/
        
        $this->_initialize();
        return $this->_coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(Closure $p)
    {
        $this->_initialize();
        return $this->_coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element)
    {
        $this->_initialize();
        return $this->_coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->_initialize();
        return $this->_coll->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys()
    {
        $this->_initialize();
        return $this->_coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        $this->_initialize();
        return $this->_coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->_initialize();
        return $this->_coll->count();
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->_initialize();
        $this->_coll->set($key, $value);
        $this->_changed();
    }

    /**
     * {@inheritdoc}
     */
    public function add($value)
    {
        $this->_coll->add($value);
        $this->_changed();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->_initialize();
        return $this->_coll->isEmpty();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $this->_initialize();
        return $this->_coll->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function map(Closure $func)
    {
        $this->_initialize();
        return $this->_coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Closure $p)
    {
        $this->_initialize();
        return $this->_coll->filter($p);
    }
    
    /**
     * {@inheritdoc}
     */
    public function forAll(Closure $p)
    {
        $this->_initialize();
        return $this->_coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(Closure $p)
    {
        $this->_initialize();
        return $this->_coll->partition($p);
    }
    
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->_initialize();
        return $this->_coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->_initialize();
        $result = $this->_coll->clear();
        if ($this->_association->isOwningSide) {
            $this->_changed();
            $this->_em->getUnitOfWork()->scheduleCollectionDeletion($this);
        }
        
        return $result;
    }
    
    /**
     * Called by PHP when this collection is serialized. Ensures that only the
     * elements are properly serialized.
     *
     * @internal Tried to implement Serializable first but that did not work well
     *           with circular references. This solution seems simpler and works well.
     */
    public function __sleep()
    {
        return array('_coll');
    }
    
    /* ArrayAccess implementation */

    /**
     * @see containsKey()
     */
    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    /**
     * @see get()
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    /**
     * @see remove()
     */
    public function offsetUnset($offset)
    {
        return $this->remove($offset);
    }
    
    public function key()
    {
        return $this->_coll->key();
    }
    
    /**
     * Gets the element of the collection at the current iterator position.
     */
    public function current()
    {
        return $this->_coll->current();
    }
    
    /**
     * Moves the internal iterator position to the next element.
     */
    public function next()
    {
        return $this->_coll->next();
    }
    
    /**
     * Retrieves the wrapped Collection instance.
     */
    public function unwrap()
    {
        return $this->_coll;
    }
}
