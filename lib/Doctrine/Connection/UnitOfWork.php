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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::ORM::Internal;

#use Doctrine::ORM::Entity;
#use Doctrine::ORM::EntityManager;
#use Doctrine::ORM::Exceptions::UnitOfWorkException;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @todo Rename: Doctrine::ORM::(Internal::)UnitOfWork.
 * @todo Turn connection exceptions into UnitOfWorkExceptions.
 */
class Doctrine_Connection_UnitOfWork
{
    /**
     * The identity map that holds references to all managed entities that have
     * an identity. The entities are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var array
     */
    protected $_identityMap = array();

    /**
     * A list of all new entities that need to be INSERTed.
     *
     * @var array
     * @todo Index by class name.
     * @todo Rename to _inserts?
     */
    protected $_newEntities = array();

    /**
     * A list of all dirty entities.
     *
     * @var array
     * @todo Rename to _updates?
     */
    protected $_dirtyEntities = array();

    /**
     * A list of all deleted entities.
     * Removed entities are entities that are "scheduled for removal" but have
     * not yet been removed from the database.
     *
     * @var array
     * @todo Rename to _deletions?
     */
    protected $_deletedEntities = array();
    
    /**
     * All collection deletions.
     *
     * @var array
     */
    protected $_collectionDeletions = array();
    
    /**
     * All collection creations.
     *
     * @var array
     */
    protected $_collectionCreations = array();
    
    /**
     * All collection updates.
     *
     * @var array
     */
    protected $_collectionUpdates = array();

    /**
     * The EntityManager the UnitOfWork belongs to.
     *
     * @var Doctrine::ORM::EntityManager
     */
    protected $_em;

    /**
     * The calculator used to calculate the order in which changes to
     * entities need to be written to the database.
     *
     * @var Doctrine::ORM::Internal::CommitOrderCalculator
     */
    protected $_commitOrderCalculator;

    /**
     * Constructor.
     * Creates a new UnitOfWork.
     *
     * @param Doctrine_EntityManager $em
     */
    public function __construct(Doctrine_EntityManager $em)
    {
        $this->_em = $em;
        //TODO: any benefit with lazy init?
        $this->_commitOrderCalculator = new Doctrine_Internal_CommitOrderCalculator();
    }

    /**
     * Commits the unit of work, executing all operations that have been postponed
     * up to this point.
     *
     * @todo Impl
     */
    public function commit()
    {
        // Detect changes in managed entities (mark dirty)
        //TODO: Consider using registerDirty() in Entity#_set() instead if its
        // more performant (SEE THERE).
        /*foreach ($this->_identityMap as $entities) {
            foreach ($entities as $entity) {
                if ($entity->_state() == Doctrine_Entity::STATE_MANAGED
                        && $entity->isModified()) {
                    $this->registerDirty($entity);
                }
            }
        }*/

        if (empty($this->_newEntities) &&
                empty($this->_deletedEntities) &&
                empty($this->_dirtyEntities)) {
            return; // Nothing to do.
        }

        // Now we need a commit order to maintain referential integrity
        $commitOrder = $this->_getCommitOrder();

        //TODO: begin transaction here?

        foreach ($commitOrder as $class) {
            $this->_executeInserts($class);
            $this->_executeUpdates($class);
        }
        
        //TODO: collection deletions
        //TODO: collection updates (deleteRows, updateRows, insertRows)
        //TODO: collection recreations

        // Entity deletions come last and need to be in reverse commit order
        for ($count = count($commitOrder), $i = $count - 1; $i >= 0; $i--) {
            $this->_executeDeletions($commitOrder[$i]);
        }

        //TODO: commit transaction here?

        // clear lists
        $this->_newEntities = array();
        $this->_dirtyEntities = array();
        $this->_deletedEntities = array();
    }

    private function _executeInserts($class)
    {
        //TODO: Maybe $persister->addInsert($entity) in the loop and
        // $persister->executeInserts() at the end to allow easy prepared
        // statement reuse and maybe bulk operations in the persister.
        // Same for update/delete.
        $className = $class->getClassName();
        $persister = $this->_em->getEntityPersister($className);
        foreach ($this->_newEntities as $entity) {
            if ($entity->getClass()->getClassName() == $className) {
                $persister->insert($entity);
            }
        }
    }

    private function _executeUpdates($class)
    {
        $className = $class->getClassName();
        $persister = $this->_em->getEntityPersister($className);
        foreach ($this->_dirtyEntities as $entity) {
            if ($entity->getClass()->getClassName() == $className) {
                $persister->update($entity);
            }
        }
    }

    private function _executeDeletions($class)
    {
        $className = $class->getClassName();
        $persister = $this->_em->getEntityPersister($className);
        foreach ($this->_deletedEntities as $entity) {
            if ($entity->getClass()->getClassName() == $className) {
                $persister->delete($entity);
            }
        }
    }

    /**
     * Gets the commit order.
     *
     * @return array
     */
    private function _getCommitOrder()
    {
        //TODO: Once these 3 arrays are indexed by classname we can do this:
        // Either way... do we need to care about duplicates?
        /*$classesInChangeSet = array_merge(
            array_keys($this->_newEntities),
            array_keys($this->_dirtyEntities),
            array_keys($this->_deletedEntities)
        );*/

        $entityChangeSet = array_merge($this->_newEntities, $this->_dirtyEntities, $this->_deletedEntities);
        
        /* if (count($entityChangeSet) == 1) {
         *     return array($entityChangeSet[0]->getClass());
         * }
         */
        
        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        $newNodes = array();
        foreach ($entityChangeSet as $entity) {
            if ( ! $this->_commitOrderCalculator->hasNodeWithKey($entity->getClass()->getClassName())) {
                $this->_commitOrderCalculator->addNodeWithItem(
                        $entity->getClass()->getClassName(), // index/key
                        $entity->getClass() // item
                        );
                $newNodes[] = $this->_commitOrderCalculator->getNodeForKey($entity->getClass()->getClassName());
            }
        }

        // Calculate dependencies for new nodes
        foreach ($newNodes as $node) {
            foreach ($node->getClass()->getAssociationMappings() as $assocMapping) {
                //TODO: should skip target classes that are not in the changeset.
                if ($assocMapping->isOwningSide()) {
                    $targetClass = $this->_em->getClassMetadata($assocMapping->getTargetEntityName());
                    $targetClassName = $targetClass->getClassName();
                    // if the target class does not yet have a node, create it
                    if ( ! $this->_commitOrderCalculator->hasNodeWithKey($targetClassName)) {
                        $this->_commitOrderCalculator->addNodeWithItem(
                                $targetClassName, // index/key
                                $targetClass // item
                                );
                    }
                    // add dependency
                    $otherNode = $this->_commitOrderCalculator->getNodeForKey($targetClassName);
                    $node->before($otherNode);
                }
            }
        }

        return $this->_commitOrderCalculator->getCommitOrder();
    }

    /**
     * Register a new entity.
     * 
     * @todo Rename to scheduleForInsert().
     */
    public function registerNew(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();

        /*if ( ! $entity->_identifier()) {
         throw new Doctrine_Connection_Exception("Entity without identity cant be registered as new.");
         }*/
        if (isset($this->_dirtyEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Dirty object can't be registered as new.");
        }
        if (isset($this->_deletedEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Removed object can't be registered as new.");
        }
        if (isset($this->_newEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Object already registered as new. Can't register twice.");
        }

        $this->_newEntities[$oid] = $entity;
        if ($entity->_identifier()) {
            $this->addToIdentityMap($entity);
        }
    }

    /**
     * Checks whether an entity is registered as new on the unit of work.
     *
     * @param Doctrine_Entity $entity
     * @return boolean
     * @todo Rename to isScheduledForInsert().
     */
    public function isRegisteredNew(Doctrine_Entity $entity)
    {
        return isset($this->_newEntities[$entity->getOid()]);
    }

    /**
     * Registers a clean entity.
     * The entity is simply put into the identity map.
     *
     * @param Doctrine::ORM::Entity $entity
     */
    public function registerClean(Doctrine_Entity $entity)
    {
        $this->addToIdentityMap($entity);
    }

    /**
     * Registers a dirty entity.
     *
     * @param Doctrine::ORM::Entity $entity
     * @todo Rename to scheduleForUpdate().
     */
    public function registerDirty(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();
        if ( ! $entity->_identifier()) {
            throw new Doctrine_Connection_Exception("Entity without identity "
                    . "can't be registered as dirty.");
        }
        if (isset($this->_deletedEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Removed object can't be registered as dirty.");
        }

        if ( ! isset($this->_dirtyEntities[$oid]) && ! isset($this->_newEntities[$oid])) {
            $this->_dirtyEntities[$oid] = $entity;
        }
    }

    /**
     * Checks whether an entity is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty entities are only registered
     * at commit time.
     *
     * @param Doctrine_Entity $entity
     * @return boolean
     * @todo Rename to isScheduledForUpdate().
     */
    public function isRegisteredDirty(Doctrine_Entity $entity)
    {
        return isset($this->_dirtyEntities[$entity->getOid()]);
    }

    /**
     * Registers a deleted entity.
     * 
     * @todo Rename to scheduleForDelete().
     */
    public function registerDeleted(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();
        if ( ! $this->isInIdentityMap($entity)) {
            return;
        }

        $this->removeFromIdentityMap($entity);

        if (isset($this->_newEntities[$oid])) {
            unset($this->_newEntities[$oid]);
            return; // entity has not been persisted yet, so nothing more to do.
        }
        /* Seems unnecessary since _dirtyEntities is filled & cleared on commit, not earlier
         if (isset($this->_dirtyEntities[$oid])) {
         unset($this->_dirtyEntities[$oid]);
         }*/
        if ( ! isset($this->_deletedEntities[$oid])) {
            $this->_deletedEntities[$oid] = $entity;
        }
    }

    /**
     * Checks whether an entity is registered as removed/deleted with the unit
     * of work.
     *
     * @param Doctrine::ORM::Entity $entity
     * @return boolean
     * @todo Rename to isScheduledForDelete().
     */
    public function isRegisteredRemoved(Doctrine_Entity $entity)
    {
        return isset($this->_deletedEntities[$entity->getOid()]);
    }

    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param integer $oid                  object identifier
     * @return boolean                      whether ot not the operation was successful
     */
    public function detach(Doctrine_Entity $entity)
    {
        if ($this->isInIdentityMap($entity)) {
            $this->removeFromIdentityMap($entity);
        }
    }

    /**
     * Enter description here...
     *
     * @param Doctrine_Entity $entity
     * @return unknown
     * @todo Rename to isScheduled()
     */
    public function isEntityRegistered(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();
        return isset($this->_newEntities[$oid]) ||
                //isset($this->_dirtyEntities[$oid]) ||
                isset($this->_deletedEntities[$oid]) ||
                $this->isInIdentityMap($entity);
    }

    /**
     * Detaches all currently managed entities.
     * Alternatively, if an entity class name is given, all entities of that type
     * (or subtypes) are detached. Don't forget that entities are registered in
     * the identity map with the name of the root entity class. So calling detachAll()
     * with a class name that is not the name of a root entity has no effect.
     *
     * @return integer   The number of detached entities.
     */
    public function detachAll($entityName = null)
    {
        //TODO: what do do with new/dirty/removed lists?
        $numDetached = 0;
        if ($entityName !== null && isset($this->_identityMap[$entityName])) {
            $numDetached = count($this->_identityMap[$entityName]);
            $this->_identityMap[$entityName] = array();
        } else {
            $numDetached = count($this->_identityMap);
            $this->_identityMap = array();
        }

        return $numDetached;
    }

    /**
     * Registers an entity in the identity map.
     * Note that entities in a hierarchy are registered with the class name of
     * the root entity.
     *
     * @param Doctrine::ORM::Entity $entity  The entity to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the entity in question is already managed.
     */
    public function addToIdentityMap(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->_identifier());
        if ($idHash === '') {
            throw new Doctrine_Connection_Exception("Entity with oid '" . $entity->getOid()
                    . "' has no identity and therefore can't be added to the identity map.");
        }
        $className = $entity->getClass()->getRootClassName();
        if (isset($this->_identityMap[$className][$idHash])) {
            return false;
        }
        $this->_identityMap[$className][$idHash] = $entity;
        $entity->_state(Doctrine_Entity::STATE_MANAGED);
        return true;
    }

    /**
     * Removes an entity from the identity map.
     *
     * @param Doctrine_Entity $entity
     * @return unknown
     */
    public function removeFromIdentityMap(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->_identifier());
        if ($idHash === '') {
            throw new Doctrine_Connection_Exception("Entity with oid '" . $entity->getOid()
                    . "' has no identity and therefore can't be removed from the identity map.");
        }
        $className = $entity->getClass()->getRootClassName();
        if (isset($this->_identityMap[$className][$idHash])) {
            unset($this->_identityMap[$className][$idHash]);
            return true;
        }

        return false;
    }

    /**
     * Finds an entity in the identity map by its identifier hash.
     *
     * @param string $idHash
     * @param string $rootClassName
     * @return Doctrine::ORM::Entity
     */
    public function getByIdHash($idHash, $rootClassName)
    {
        return $this->_identityMap[$rootClassName][$idHash];
    }
    public function tryGetByIdHash($idHash, $rootClassName)
    {
        if ($this->containsIdHash($idHash, $rootClassName)) {
            return $this->getByIdHash($idHash, $rootClassName);
        }
        return false;
    }

    /**
     * Gets the identifier hash for a set of identifier values.
     * The hash is just a concatenation of the identifier values.
     * The identifiers are concatenated with a space.
     * 
     * Note that this method always returns a string. If the given array is
     * empty, an empty string is returned.
     *
     * @param array $id
     * @return string  The hash.
     */
    public function getIdentifierHash(array $id)
    {
        return implode(' ', $id);
    }

    /**
     * Checks whether an entity is registered in the identity map of the
     * UnitOfWork.
     *
     * @param Doctrine_Entity $entity
     * @return boolean
     */
    public function isInIdentityMap(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->_identifier());        
        if ($idHash === '') {
            return false;
        }
        
        return isset($this->_identityMap
                [$entity->getClass()->getRootClassName()]
                [$idHash]
                );
    }

    /**
     * Checks whether an identifier hash exists in the identity map.
     *
     * @param string $idHash
     * @param string $rootClassName
     * @return boolean
     */
    public function containsIdHash($idHash, $rootClassName)
    {
        return isset($this->_identityMap[$rootClassName][$idHash]);
    }

    /**
     * Saves an entity as part of the current unit of work.
     *
     * @param Doctrine_Entity $entity  The entity to save.
     */
    public function save(Doctrine_Entity $entity)
    {
        $insertNow = array();
        $visited = array();
        $this->_doSave($entity, $visited, $insertNow);
        if ( ! empty($insertNow)) {
            // We have no choice. This means that there are either new entities
            // with an IDENTITY key generation or with a natural identifier.
            // In both cases we must commit the inserts instantly.
            //TODO: Isnt it enough to only execute the inserts instead of full flush?
            $this->commit();
            /* The following may be better:
            $commitOrder = $this->_getCommitOrder($insertNow);
            foreach ($commitOrder as $class) {
                $this->_executeInserts($class);
            }
            //... remove them from _newEntities, or dont store them there in the first place
            */
        }
    }

    /**
     * Saves an entity as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param Doctrine_Entity $entity  The entity to save.
     * @param array $visited  The already visited entities.
     */
    private function _doSave(Doctrine_Entity $entity, array &$visited, array &$insertNow)
    {
        if (isset($visited[$entity->getOid()])) {
            return; // Prevent infinite recursion
        }

        $visited[$entity->getOid()] = $entity; // mark visited

        $class = $entity->getClass();
        switch ($entity->_state()) {
            case Doctrine_Entity::STATE_MANAGED:
                // nothing to do for $entity
                break;
            case Doctrine_Entity::STATE_NEW:
                if ($class->isIdGeneratorIdentity()) {
                    $insertNow[$entity->getOid()] = $entity;
                    $this->_newEntities[$entity->getOid()] = $entity;
                } else if ( ! $class->usesIdGenerator()) {
                    $insertNow[$entity->getOid()] = $entity;
                    $this->_newEntities[$entity->getOid()] = $entity;
                    //...
                } else if ($class->isIdGeneratorSequence()) {
                    // Get the next sequence number
                    //TODO: sequence name?
                    $id = $this->_em->getConnection()->getSequenceManager()->nextId("foo");
                    $entity->set($class->getSingleIdentifierFieldName(), $id);
                    $this->registerNew($entity);
                } else {
                    throw new Doctrine_Exception("Unable to handle ID generation of new entity.");
                }
                break;
            case Doctrine_Entity::STATE_DETACHED:
                //exception?
                throw new Doctrine_Exception("Behavior of save() for a detached entity "
                        . "is not yet defined.");
            case Doctrine_Entity::STATE_DELETED:
                // $entity becomes managed again
                if ($this->isRegisteredRemoved($entity)) {
                    //TODO: better a method for this?
                    unset($this->_deletedEntities[$entity->getOid()]);
                } else {
                    //FIXME: There's more to think of here...
                    $this->registerNew($entity);
                }
                break;
            default:
                //TODO: throw UnitOfWorkException::invalidEntityState()
                throw new Doctrine_Exception("Encountered invalid entity state.");
        }
        
        $this->_cascadeSave($entity, $visited, $insertNow);
    }

    /**
     * Deletes an entity as part of the current unit of work.
     *
     * @param Doctrine_Entity $entity
     */
    public function delete(Doctrine_Entity $entity)
    {
        $this->_doDelete($entity, array());
    }

    private function _doDelete(Doctrine_Entity $entity, array &$visited)
    {
        if (isset($visited[$entity->getOid()])) {
            return; // Prevent infinite recursion
        }

        $visited[$entity->getOid()] = $entity; // mark visited

        $class = $entity->getClass();
        switch ($entity->_state()) {
            case Doctrine_Entity::STATE_NEW:
            case Doctrine_Entity::STATE_DELETED:
                // nothing to do for $entity
                break;
            case Doctrine_Entity::STATE_MANAGED:
                $this->registerDeleted($entity);
                break;
            case Doctrine_Entity::STATE_DETACHED:
                //exception?
                throw new Doctrine_Exception("A detached entity can't be deleted.");
            default:
                //TODO: throw UnitOfWorkException::invalidEntityState()
                throw new Doctrine_Exception("Encountered invalid entity state.");
        }

        $this->_cascadeDelete($entity, $visited);
    }

    /**
     * Cascades the save operation to associated entities.
     *
     * @param Doctrine_Entity $entity
     * @param array $visited
     */
    private function _cascadeSave(Doctrine_Entity $entity, array &$visited, array &$insertNow)
    {
        foreach ($entity->getClass()->getAssociationMappings() as $assocMapping) {
            if ( ! $assocMapping->isCascadeSave()) {
                continue;
            }
            $relatedEntities = $entity->get($assocMapping->getSourceFieldName());
            if ($relatedEntities instanceof Doctrine_Entity) {
                $this->_doSave($relatedEntities, $visited, $insertNow);
            } else if ($relatedEntities instanceof Doctrine_Collection &&
                    count($relatedEntities) > 0) {
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doSave($relatedEntity, $visited, $insertNow);
                }
            }
        }
    }

    private function _cascadeDelete(Doctrine_Entity $entity)
    {

    }
    
    public function getCommitOrderCalculator()
    {
        return $this->_commitOrderCalculator;
    }

    public function close()
    {
        //...        
        $this->_commitOrderCalculator->clear();
    }
    
    public function scheduleCollectionUpdate(Doctrine_Collection $coll)
    {
        $this->_collectionUpdates[] = $coll;
    }
    
    public function isCollectionScheduledForUpdate(Doctrine_Collection $coll)
    {
        //...
    }
    
    public function scheduleCollectionDeletion(Doctrine_Collection $coll)
    {
        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        $this->_collectionDeletions[] = $coll;
    }
    
    public function isCollectionScheduledForDeletion(Doctrine_Collection $coll)
    {
        //...
    }
    
    public function scheduleCollectionRecreation(Doctrine_Collection $coll)
    {
        $this->_collectionRecreations[] = $coll;
    }
    
    public function isCollectionScheduledForRecreation(Doctrine_Collection $coll)
    {
        //...
    }
    

    // Stuff from 0.11/1.0 that we will need later (need to modify it though)

    /**
     * Collects all records that need to be deleted by applying defined
     * application-level delete cascades.
     *
     * @param array $deletions  Map of the records to delete. Keys=Oids Values=Records.
     */
    /*private function _collectDeletions(Doctrine_Record $record, array &$deletions)
     {
     if ( ! $record->exists()) {
     return;
     }

     $deletions[$record->getOid()] = $record;
     $this->_cascadeDelete($record, $deletions);
     }*/

    /**
     * Cascades an ongoing delete operation to related objects. Applies only on relations
     * that have 'delete' in their cascade options.
     * This is an application-level cascade. Related objects that participate in the
     * cascade and are not yet loaded are fetched from the database.
     * Exception: many-valued relations are always (re-)fetched from the database to
     * make sure we have all of them.
     *
     * @param Doctrine_Record  The record for which the delete operation will be cascaded.
     * @throws PDOException    If something went wrong at database level
     * @return void
     */
    /*protected function _cascadeDelete(Doctrine_Record $record, array &$deletions)
     {
     foreach ($record->getTable()->getRelations() as $relation) {
     if ($relation->isCascadeDelete()) {
     $fieldName = $relation->getAlias();
     // if it's a xToOne relation and the related object is already loaded
     // we don't need to refresh.
     if ( ! ($relation->getType() == Doctrine_Relation::ONE && isset($record->$fieldName))) {
     $record->refreshRelated($relation->getAlias());
     }
     $relatedObjects = $record->get($relation->getAlias());
     if ($relatedObjects instanceof Doctrine_Record && $relatedObjects->exists()
     && ! isset($deletions[$relatedObjects->getOid()])) {
     $this->_collectDeletions($relatedObjects, $deletions);
     } else if ($relatedObjects instanceof Doctrine_Collection && count($relatedObjects) > 0) {
     // cascade the delete to the other objects
     foreach ($relatedObjects as $object) {
     if ( ! isset($deletions[$object->getOid()])) {
     $this->_collectDeletions($object, $deletions);
     }
     }
     }
     }
     }
     }*/

    /**
     * Executes the deletions for all collected records during a delete operation
     * (usually triggered through $record->delete()).
     *
     * @param array $deletions  Map of the records to delete. Keys=Oids Values=Records.
     */
    /*private function _executeDeletions(array $deletions)
     {
     // collect class names
     $classNames = array();
     foreach ($deletions as $record) {
     $classNames[] = $record->getTable()->getComponentName();
     }
     $classNames = array_unique($classNames);

     // order deletes
     $executionOrder = $this->buildFlushTree($classNames);

     // execute
     try {
     $this->conn->beginInternalTransaction();

     for ($i = count($executionOrder) - 1; $i >= 0; $i--) {
     $className = $executionOrder[$i];
     $table = $this->conn->getTable($className);

     // collect identifiers
     $identifierMaps = array();
     $deletedRecords = array();
     foreach ($deletions as $oid => $record) {
     if ($record->getTable()->getComponentName() == $className) {
     $veto = $this->_preDelete($record);
     if ( ! $veto) {
     $identifierMaps[] = $record->identifier();
     $deletedRecords[] = $record;
     unset($deletions[$oid]);
     }
     }
     }

     if (count($deletedRecords) < 1) {
     continue;
     }

     // extract query parameters (only the identifier values are of interest)
     $params = array();
     $columnNames = array();
     foreach ($identifierMaps as $idMap) {
     while (list($fieldName, $value) = each($idMap)) {
     $params[] = $value;
     $columnNames[] = $table->getColumnName($fieldName);
     }
     }
     $columnNames = array_unique($columnNames);

     // delete
     $tableName = $table->getTableName();
     $sql = "DELETE FROM " . $this->conn->quoteIdentifier($tableName) . " WHERE ";

     if ($table->isIdentifierComposite()) {
     $sql .= $this->_buildSqlCompositeKeyCondition($columnNames, count($identifierMaps));
     $this->conn->exec($sql, $params);
     } else {
     $sql .= $this->_buildSqlSingleKeyCondition($columnNames, count($params));
     $this->conn->exec($sql, $params);
     }

     // adjust state, remove from identity map and inform postDelete listeners
     foreach ($deletedRecords as $record) {
     // currently just for bc!
     $this->_deleteCTIParents($table, $record);
     //--
     $record->state(Doctrine_Record::STATE_TCLEAN);
     $record->getTable()->removeRecord($record);
     $this->_postDelete($record);
     }
     }

     $this->conn->commit();
     // trigger postDelete for records skipped during the deletion (veto!)
     foreach ($deletions as $skippedRecord) {
     $this->_postDelete($skippedRecord);
     }

     return true;
     } catch (Exception $e) {
     $this->conn->rollback();
     throw $e;
     }
     }*/

    /**
     * Builds the SQL condition to target multiple records who have a single-column
     * primary key.
     *
     * @param Doctrine_Table $table  The table from which the records are going to be deleted.
     * @param integer $numRecords  The number of records that are going to be deleted.
     * @return string  The SQL condition "pk = ? OR pk = ? OR pk = ? ..."
     */
    /*private function _buildSqlSingleKeyCondition($columnNames, $numRecords)
     {
     $idColumn = $this->conn->quoteIdentifier($columnNames[0]);
     return implode(' OR ', array_fill(0, $numRecords, "$idColumn = ?"));
     }*/

    /**
     * Builds the SQL condition to target multiple records who have a composite primary key.
     *
     * @param Doctrine_Table $table  The table from which the records are going to be deleted.
     * @param integer $numRecords  The number of records that are going to be deleted.
     * @return string  The SQL condition "(pk1 = ? AND pk2 = ?) OR (pk1 = ? AND pk2 = ?) ..."
     */
    /*private function _buildSqlCompositeKeyCondition($columnNames, $numRecords)
     {
     $singleCondition = "";
     foreach ($columnNames as $columnName) {
     $columnName = $this->conn->quoteIdentifier($columnName);
     if ($singleCondition === "") {
     $singleCondition .= "($columnName = ?";
     } else {
     $singleCondition .= " AND $columnName = ?";
     }
     }
     $singleCondition .= ")";
     $fullCondition = implode(' OR ', array_fill(0, $numRecords, $singleCondition));

     return $fullCondition;
     }*/
    
     public function getIdentityMap()
     {
         return $this->_identityMap;
     }
}




