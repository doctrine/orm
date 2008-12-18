<?php
/*
 *  $Id: UnitOfWork.php 4947 2008-09-12 13:16:05Z romanb $
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

#namespace Doctrine\ORM;

#use Doctrine\ORM\EntityManager;
#use Doctrine\ORM\Exceptions\UnitOfWorkException;

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database
 * in the correct order.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision: 4947 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @todo Rename: Doctrine::ORM::UnitOfWork.
 * @todo Turn connection exceptions into UnitOfWorkExceptions.
 */
class Doctrine_ORM_UnitOfWork
{
    /**
     * An Entity is in managed state when it has a primary key/identifier (and
     * therefore persistent state) and is managed by an EntityManager
     * (registered in the identity map).
     * In MANAGED state the entity is associated with an EntityManager that manages
     * the persistent state of the Entity.
     */
    const STATE_MANAGED = 1;

    /**
     * An Entity is new if it does not yet have an identifier/primary key
     * and is not (yet) managed by an EntityManager.
     */
    const STATE_NEW = 2;

    /**
     * An Entity is temporarily locked during deletes and saves.
     *
     * This state is used internally to ensure that circular deletes
     * and saves will not cause infinite loops.
     * @todo Not sure this is a good idea. It is a problematic solution because
     * it hides the original state while the locked state is active.
     */
    const STATE_LOCKED = 6;

    /**
     * A detached Entity is an instance with a persistent identity that is not
     * (or no longer) associated with an EntityManager (and a UnitOfWork).
     * This means its no longer in the identity map.
     */
    const STATE_DETACHED = 3;

    /**
     * A removed Entity instance is an instance with a persistent identity,
     * associated with an EntityManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_DELETED = 4;

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
     * Map of the original entity data of entities fetched from the database.
     * Keys are object ids. This is used for calculating changesets at commit time.
     * Note that PHPs "copy-on-write" behavior helps a lot with the potentially
     * high memory usage.
     *
     * @var array
     */
    protected $_originalEntityData = array();

    /**
     * Map of data changes. Keys are object ids.
     * Filled at the beginning of a commit() of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    protected $_dataChangeSets = array();

    /**
     * The states of entities in this UnitOfWork.
     *
     * @var array
     */
    protected $_entityStates = array();

    /**
     * A list of all new entities that need to be INSERTed.
     *
     * @var array
     * @todo Index by class name.
     * @todo Rename to _inserts?
     */
    protected $_newEntities = array();

    /**
     * A list of all dirty entities that need to be UPDATEd.
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
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * The calculator used to calculate the order in which changes to
     * entities need to be written to the database.
     *
     * @var Doctrine\ORM\Internal\CommitOrderCalculator
     */
    protected $_commitOrderCalculator;

    /**
     * Constructor.
     * Creates a new UnitOfWork.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(Doctrine_ORM_EntityManager $em)
    {
        $this->_em = $em;
        //TODO: any benefit with lazy init?
        $this->_commitOrderCalculator = new Doctrine_ORM_Internal_CommitOrderCalculator();
    }

    /**
     * Commits the unit of work, executing all operations that have been postponed
     * up to this point.
     *
     * @return void
     */
    public function commit()
    {
        // Compute changes in managed entities
        $this->_computeDataChangeSet();

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
        }
        foreach ($commitOrder as $class) {
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

        // clear up
        $this->_newEntities = array();
        $this->_dirtyEntities = array();
        $this->_deletedEntities = array();
        $this->_dataChangeSets = array();
    }

    /**
     * Gets the data changeset for an entity.
     *
     * @return array
     */
    public function getDataChangeSet($entity)
    {
        $oid = spl_object_id($entity);
        if (isset($this->_dataChangeSets[$oid])) {
            return $this->_dataChangeSets[$oid];
        }
        return array();
    }

    /**
     * Computes all the changes that have been done to entities in the identity map
     * and stores these changes in _dataChangeSet temporarily for access by the
     * peristers, until the UoW commit is finished.
     *
     * @param array $entities The entities for which to compute the changesets. If this
     *          parameter is not specified, the changesets of all entities in the identity
     *          map are computed.
     * @return void
     */
    private function _computeDataChangeSet(array $entities = null)
    {
        $entitySet = array();
        if ( ! is_null($entities)) {
            foreach ($entities as $entity) {
                $className = get_class($entity);
                if ( ! isset($entitySet[$className])) {
                    $entitySet[$className] = array();
                }
                $entitySet[$className][] = $entity;
            }
        } else {
            $entitySet = $this->_identityMap;
        }
        
        foreach ($entitySet as $className => $entities) {
            $class = $this->_em->getClassMetadata($className);
            foreach ($entities as $entity) {
                $oid = spl_object_id($entity);
                if ($this->getEntityState($entity) == self::STATE_MANAGED) {
                    if ( ! $class->isInheritanceTypeNone()) {
                        $class = $this->_em->getClassMetadata(get_class($entity));
                    }

                    $actualData = array();
                    foreach ($class->getReflectionProperties() as $name => $refProp) {
                        $actualData[$name] = $refProp->getValue($entity);
                    }

                    if ( ! isset($this->_originalEntityData[$oid])) {
                        $this->_dataChangeSets[$oid] = $actualData;
                    } else {
                        $originalData = $this->_originalEntityData[$oid];
                        $changeSet = array();
                        foreach ($actualData as $propName => $actualValue) {
                            $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;
                            if (is_object($orgValue) && $orgValue !== $actualValue) {
                                $changeSet[$propName] = array($orgValue => $actualValue);
                            } else if ($orgValue != $actualValue || (is_null($orgValue) xor is_null($actualValue))) {
                                $changeSet[$propName] = array($orgValue => $actualValue);
                            }
                        }
                        $this->_dirtyEntities[$oid] = $entity;
                        $this->_dataChangeSets[$oid] = $changeSet;
                    }
                }
                if (isset($this->_dirtyEntities[$oid])) {
                    $this->_originalEntityData[$oid] = $actualData;
                }
            }
        }
    }

    /**
     * Executes all entity insertions for entities of the specified type.
     *
     * @param Doctrine::ORM::Mapping::ClassMetadata $class
     */
    private function _executeInserts($class)
    {
        //TODO: Maybe $persister->addInsert($entity) in the loop and
        // $persister->executeInserts() at the end to allow easy prepared
        // statement reuse and maybe bulk operations in the persister.
        // Same for update/delete.
        $className = $class->getClassName();
        $persister = $this->_em->getEntityPersister($className);
        foreach ($this->_newEntities as $entity) {
            if (get_class($entity) == $className) {
                $persister->insert($entity);
                if ($class->isIdGeneratorIdentity()) {
                    $id = $this->_em->getIdGenerator($class->getIdGeneratorType());
                    $class->setEntityIdentifier($entity, $id);
                    $this->_entityStates[spl_object_id($oid)] = self::STATE_MANAGED;
                }
            }
        }
    }

    /**
     * Executes all entity updates for entities of the specified type.
     *
     * @param Doctrine::ORM::Mapping::ClassMetadata $class
     */
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

    /**
     * Executes all entity deletions for entities of the specified type.
     *
     * @param Doctrine::ORM::Mapping::ClassMetadata $class
     */
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
    private function _getCommitOrder(array $entityChangeSet = null)
    {
        //TODO: Once these 3 arrays are indexed by classname we can do this:
        // Either way... do we need to care about duplicates?
        /*$classesInChangeSet = array_merge(
            array_keys($this->_newEntities),
            array_keys($this->_dirtyEntities),
            array_keys($this->_deletedEntities)
        );*/
        
        if (is_null($entityChangeSet)) {
            $entityChangeSet = array_merge($this->_newEntities, $this->_dirtyEntities, $this->_deletedEntities);
        }
        
        /* if (count($entityChangeSet) == 1) {
         *     return array($entityChangeSet[0]->getClass());
         * }
         */
        
        // See if there are any new classes in the changeset, that are not in the
        // commit order graph yet (dont have a node).
        $newNodes = array();
        foreach ($entityChangeSet as $entity) {
            $className = get_class($entity);
            if ( ! $this->_commitOrderCalculator->hasNodeWithKey($className)) {
                $this->_commitOrderCalculator->addNodeWithItem(
                        $className, // index/key
                        $this->_em->getClassMetadata($className) // item
                        );
                $newNodes[] = $this->_commitOrderCalculator->getNodeForKey($className);
            }
        }

        // Calculate dependencies for new nodes
        foreach ($newNodes as $node) {
            $class = $node->getClass();
            foreach ($class->getAssociationMappings() as $assocMapping) {
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
                    $otherNode->before($node);
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
    public function registerNew($entity)
    {
        $oid = spl_object_id($entity);

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
        if ($this->_em->getClassMetadata(get_class($entity))->getEntityIdentifier($entity)) {
            $this->addToIdentityMap($entity);
        }
    }

    /**
     * Checks whether an entity is registered as new on the unit of work.
     *
     * @param Doctrine_ORM_Entity $entity
     * @return boolean
     * @todo Rename to isScheduledForInsert().
     */
    public function isRegisteredNew($entity)
    {
        return isset($this->_newEntities[spl_object_id($entity)]);
    }

    /**
     * Registers a clean entity.
     * The entity is simply put into the identity map.
     *
     * @param object $entity
     */
    public function registerClean($entity)
    {
        $this->addToIdentityMap($entity);
    }

    /**
     * Registers a dirty entity.
     *
     * @param Doctrine::ORM::Entity $entity
     * @todo Rename to scheduleForUpdate().
     */
    public function registerDirty($entity)
    {
        $oid = spl_object_id($entity);
        if ( ! $entity->_identifier()) {
            throw new Doctrine_Exception("Entity without identity "
                    . "can't be registered as dirty.");
        }
        if (isset($this->_deletedEntities[$oid])) {
            throw new Doctrine_Exception("Removed object can't be registered as dirty.");
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
    public function isRegisteredDirty($entity)
    {
        return isset($this->_dirtyEntities[spl_object_id($entity)]);
    }

    /**
     * Registers a deleted entity.
     * 
     * @todo Rename to scheduleForDelete().
     */
    public function registerDeleted($entity)
    {
        $oid = spl_object_id($entity);
        if ( ! $this->isInIdentityMap($entity)) {
            return;
        }

        $this->removeFromIdentityMap($entity);

        if (isset($this->_newEntities[$oid])) {
            unset($this->_newEntities[$oid]);
            return; // entity has not been persisted yet, so nothing more to do.
        }

        if (isset($this->_dirtyEntities[$oid])) {
            unset($this->_dirtyEntities[$oid]);
        }
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
    public function isRegisteredRemoved($entity)
    {
        return isset($this->_deletedEntities[spl_object_id($entity)]);
    }

    /**
     * Detaches an entity from the persistence management. It's persistence will
     * no longer be managed by Doctrine.
     *
     * @param integer $oid                  object identifier
     * @return boolean                      whether ot not the operation was successful
     */
    public function detach($entity)
    {
        if ($this->isInIdentityMap($entity)) {
            $this->removeFromIdentityMap($entity);
        }
    }

    /**
     * Enter description here...
     *
     * @param Doctrine_ORM_Entity $entity
     * @return unknown
     * @todo Rename to isScheduled()
     */
    public function isEntityRegistered($entity)
    {
        $oid = spl_object_id($entity);
        return isset($this->_newEntities[$oid]) ||
                isset($this->_dirtyEntities[$oid]) ||
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
     * @param Doctrine\ORM\Entity $entity  The entity to register.
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the entity in question is already managed.
     */
    public function addToIdentityMap($entity)
    {
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = $this->getIdentifierHash($classMetadata->getEntityIdentifier($entity));
        if ($idHash === '') {
            throw new Doctrine_Exception("Entity with oid '" . spl_object_id($entity)
                    . "' has no identity and therefore can't be added to the identity map.");
        }
        $className = $classMetadata->getRootClassName();
        if (isset($this->_identityMap[$className][$idHash])) {
            return false;
        }
        $this->_identityMap[$className][$idHash] = $entity;
        return true;
    }

    /**
     * Gets the state of an entity within the current unit of work.
     *
     * @param Doctrine\ORM\Entity $entity
     * @return int
     */
    public function getEntityState($entity)
    {
        $oid = spl_object_id($entity);
        return isset($this->_entityStates[$oid]) ? $this->_entityStates[$oid] :
                self::STATE_NEW;
    }

    /**
     * Removes an entity from the identity map.
     *
     * @param Doctrine_ORM_Entity $entity
     * @return unknown
     */
    public function removeFromIdentityMap($entity)
    {
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = $this->getIdentifierHash($classMetadata->getEntityIdentifier($entity));
        if ($idHash === '') {
            throw new Doctrine_Exception("Entity with oid '" . spl_object_id($entity)
                    . "' has no identity and therefore can't be removed from the identity map.");
        }
        $className = $classMetadata->getRootClassName();
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

    /**
     * Tries to get an entity by its identifier hash. If no entity is found for
     * the given hash, FALSE is returned.
     *
     * @param <type> $idHash
     * @param <type> $rootClassName
     * @return mixed The found entity or FALSE.
     */
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
     * @param object $entity
     * @return boolean
     */
    public function isInIdentityMap($entity)
    {
        $classMetadata = $this->_em->getClassMetadata(get_class($entity));
        $idHash = $this->getIdentifierHash($classMetadata->getEntityIdentifier($entity));     
        if ($idHash === '') {
            return false;
        }
        
        return isset($this->_identityMap
                [$classMetadata->getRootClassName()]
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
     * @param Doctrine_ORM_Entity $entity  The entity to save.
     */
    public function save($entity)
    {
        $insertNow = array();
        $visited = array();
        $this->_doSave($entity, $visited, $insertNow);
        if ( ! empty($insertNow)) {
            // We have no choice. This means that there are new entities
            // with an IDENTITY column key generation.
            $this->_computeDataChangeSet($insertNow);
            $commitOrder = $this->_getCommitOrder($insertNow);
            foreach ($commitOrder as $class) {
                $this->_executeInserts($class);
            }
            // remove them from _newEntities
            $this->_newEntities = array_diff_key($this->_newEntities, $insertNow);
            $this->_dataChangeSets = array();
        }
    }

    /**
     * Saves an entity as part of the current unit of work.
     * This method is internally called during save() cascades as it tracks
     * the already visited entities to prevent infinite recursions.
     *
     * @param Doctrine_ORM_Entity $entity  The entity to save.
     * @param array $visited  The already visited entities.
     */
    private function _doSave($entity, array &$visited, array &$insertNow)
    {
        $oid = spl_object_id($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        $class = $this->_em->getClassMetadata(get_class($entity));
        switch ($this->getEntityState($entity)) {
            case self::STATE_MANAGED:
                // nothing to do
                break;
            case self::STATE_NEW:
                $result = $this->_em->getIdGenerator($class->getClassName())->generate($entity);
                if ($result == Doctrine_ORM_Id_AbstractIdGenerator::POST_INSERT_INDICATOR) {
                    $insertNow[$oid] = $entity;
                } else {
                    $class->setEntityIdentifier($entity, $result);
                    $this->_entityStates[$oid] = self::STATE_MANAGED;
                }
                $this->registerNew($entity);
                break;
            case self::STATE_DETACHED:
                //exception?
                throw new Doctrine_Exception("Behavior of save() for a detached entity "
                        . "is not yet defined.");
            case self::STATE_DELETED:
                // $entity becomes managed again
                if ($this->isRegisteredRemoved($entity)) {
                    //TODO: better a method for this?
                    unset($this->_deletedEntities[$oid]);
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
     * @param Doctrine_ORM_Entity $entity
     */
    public function delete($entity)
    {
        $this->_doDelete($entity, array());
    }

    /**
     * Enter description here...
     *
     * @param Doctrine_ORM_Entity $entity
     * @param array $visited
     */
    private function _doDelete($entity, array &$visited)
    {
        $oid = spl_object_id($entity);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $entity; // mark visited

        //$class = $entity->getClass();
        switch ($this->getEntityState($entity)) {
            case self::STATE_NEW:
            case self::STATE_DELETED:
                // nothing to do for $entity
                break;
            case self::STATE_MANAGED:
                $this->registerDeleted($entity);
                break;
            case self::STATE_DETACHED:
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
     * @param Doctrine\ORM\Entity $entity
     * @param array $visited
     */
    private function _cascadeSave($entity, array &$visited, array &$insertNow)
    {
        $class = $this->_em->getClassMetadata(get_class($entity));
        foreach ($class->getAssociationMappings() as $assocMapping) {
            if ( ! $assocMapping->isCascadeSave()) {
                continue;
            }
            $relatedEntities = $class->getReflectionProperty($assocMapping->getSourceFieldName())
                    ->getValue($entity);
            if ($relatedEntities instanceof Doctrine_ORM_Collection &&
                    count($relatedEntities) > 0) {
                foreach ($relatedEntities as $relatedEntity) {
                    $this->_doSave($relatedEntity, $visited, $insertNow);
                }
            } else if (is_object($relatedEntities)) {
                $this->_doSave($relatedEntities, $visited, $insertNow);
            }
        }
    }

    private function _cascadeDelete($entity)
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

    /**
     * Creates an entity. Used for reconstitution as well as initial creation.
     *
     * @param string $className  The name of the entity class.
     * @param array $data  The data for the entity.
     * @return Doctrine\ORM\Entity
     */
    public function createEntity($className, array $data, Doctrine_Query $query = null)
    {
        $className = $this->_inferCorrectClassName($data, $className);
        $classMetadata = $this->_em->getClassMetadata($className);
        if ( ! empty($data)) {
            $identifierFieldNames = $classMetadata->getIdentifier();
            $isNew = false;
            foreach ($identifierFieldNames as $fieldName) {
                if ( ! isset($data[$fieldName])) {
                    // id field not found return new entity
                    $isNew = true;
                    break;
                }
                $id[] = $data[$fieldName];
            }

            if ($isNew) {
                $entity = new $className;
            } else {
                $idHash = $this->getIdentifierHash($id);
                $entity = $this->tryGetByIdHash($idHash, $classMetadata->getRootClassName());
                if ($entity) {
                    $this->_mergeData($entity, $data, $classMetadata/*, $query->getHint('doctrine.refresh')*/);
                    return $entity;
                } else {
                    $entity = new $className;
                    $this->_mergeData($entity, $data, $classMetadata, true);
                    $this->addToIdentityMap($entity);
                }
            }
        } else {
            $entity = new $className;
        }

        $this->_originalEntityData[spl_object_id($entity)] = $data;

        return $entity;
    }

    /**
     * Merges the given data into the given entity, optionally overriding
     * local changes.
     *
     * @param Doctrine\ORM\Entity $entity
     * @param array $data
     * @param boolean $overrideLocalChanges
     * @return void
     */
    private function _mergeData($entity, array $data, $class, $overrideLocalChanges = false) {
        if ($overrideLocalChanges) {
            foreach ($data as $field => $value) {
                $class->getReflectionProperty($field)->setValue($entity, $value);
            }
        } else {
            $oid = spl_object_id($entity);
            foreach ($data as $field => $value) {
                $currentValue = $class->getReflectionProperty($field)->getValue($entity);
                if ( ! isset($this->_originalEntityData[$oid]) ||
                        $currentValue == $this->_originalEntityData[$oid]) {
                    $class->getReflectionProperty($field)->setValue($entity, $value);
                }
            }
        }
    }

    /**
     * Check the dataset for a discriminator column to determine the correct
     * class to instantiate. If no discriminator column is found, the given
     * classname will be returned.
     *
     * @param array $data
     * @param string $className
     * @return string The name of the class to instantiate.
     */
    private function _inferCorrectClassName(array $data, $className)
    {
        $class = $this->_em->getClassMetadata($className);

        $discCol = $class->getInheritanceOption('discriminatorColumn');
        if ( ! $discCol) {
            return $className;
        }

        $discMap = $class->getInheritanceOption('discriminatorMap');

        if (isset($data[$discCol], $discMap[$data[$discCol]])) {
            return $discMap[$data[$discCol]];
        } else {
            return $className;
        }
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array
     */
    public function getIdentityMap()
    {
        return $this->_identityMap;
    }
}




