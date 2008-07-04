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

/**
 * The UnitOfWork is responsible for tracking changes to objects during an
 * "object-level" transaction and for writing out changes to the database at
 * in the correct order.
 * 
 * Some terminology:
 * 
 * <b>New entity</b>: A new entity is an entity that already has an identity but
 * is not yet persisted into the database. This is usually the case for all
 * newly saved/persisted entities that use a SEQUENCE id generator. Entities with an
 * IDENTITY id generator get persisted as soon as they're saved in order to
 * obtain the identifier. Therefore entities that use an IDENTITY id generator
 * never appear in the list of new entities of the UoW.
 * New entities are inserted into the database when the is UnitOfWork committed.
 * 
 * <b>Dirty entity</b>: A dirty entity is a managed entity whose values have
 * been altered.
 * 
 * <b>Removed entity</b>: A removed entity is a managed entity that is scheduled
 * for deletion from the database.
 * 
 * <b>Clean entity</b>: A clean entity is a managed entity that has been fetched
 * from the database and whose values have not yet been altered.
 *
 * @package     Doctrine
 * @subpackage  Connection
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @todo package:orm. Figure out a useful implementation.
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
     * A list of all new entities.
     */
    protected $_newEntities = array();
    
    /**
     * A list of all dirty entities.
     */
    protected $_dirtyEntities = array();
    
    /**
     * A list of all removed entities.
     */
    protected $_removedEntities = array();
    
    /**
     * The EntityManager the unit of work belongs to.
     */
    protected $_em;
    
    /**
     * The calculator used to calculate the order in which changes to
     * entities need to be written to the database.
     *
     * @var unknown_type
     * @todo Implementation. Replace buildFlushTree().
     */
    protected $_commitOrderCalculator;
    
    /**
     * Constructor.
     * Created a new UnitOfWork.
     *
     * @param Doctrine_EntityManager $em
     */
    public function __construct(Doctrine_EntityManager $em)
    {
        $this->_em = $em;
    }
    
    /**
     * Commits the unit of work, executing all operations that have been postponed
     * up to this point.
     *
     */
    public function commit()
    {
        $this->_orderCommits();
        
        $this->_insertNew();
        $this->_updateDirty();
        $this->_deleteRemoved();
    }
    
    private function _orderCommits()
    {

    }
    
    /**
     * Register a new entity.
     */
    public function registerNew(Doctrine_Entity $entity)
    {
        if ( ! $entity->identifier()) {
            throw new Doctrine_Connection_Exception("Entity without identity "
                    . "can't be registered as new.");
        }
        
        $oid = $entity->getOid();
        
        if (isset($this->_dirtyEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Dirty object can't be registered as new.");
        } else if (isset($this->_removedEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Removed object can't be registered as new.");
        } else if (isset($this->_newEntities[$oid])) {
            throw new Doctrine_Connection_Exception("Object already registered as new. Can't register twice.");
        }
        
        $this->registerIdentity($entity);
        $this->_newEntities[$oid] = $entity;
    }
    
    public function isRegisteredNew(Doctrine_Entity $entity)
    {
        return isset($this->_newEntities[$entity->getOid()]);
    }
    
    /**
     * Registers a clean entity.
     */
    public function registerClean(Doctrine_Entity $entity)
    {
        $this->registerIdentity($entity);
    }
    
    /**
     * Registers a dirty entity.
     */
    public function registerDirty(Doctrine_Entity $entity)
    {
        if ( ! $entity->identifier()) {
            throw new Doctrine_Connection_Exception("Entity without identity "
                    . "can't be registered as dirty.");
        }
        $oid = $entity->getOid();
        if (isset($this->_removedEntities[$entity->getOid()])) {
            throw new Doctrine_Connection_Exception("Removed object can't be registered as dirty.");
        }
        if ( ! isset($this->_dirtyEntities[$oid], $this->_newEntities[$oid])) {
            $this->_dirtyEntities[$entity->getOid()] = $entity;
        }
    }
    
    public function isRegisteredDirty(Doctrine_Entity $entity)
    {
        return isset($this->_dirtyEntities[$entity->getOid()]);
    }
    
    /** 
     * Registers a deleted entity.
     */
    public function registerRemoved(Doctrine_Entity $entity)
    {
        if ($entity->isTransient()) {
            return;
        }
        $this->unregisterIdentity($entity);
        $oid = $entity->getOid();
        if (isset($this->_newEntities[$oid])) {
            unset($this->_newEntities[$oid]);
            return;
        }
        if (isset($this->_dirtyEntities[$oid])) {
            unset($this->_dirtyEntities[$oid]);
        }
        if ( ! isset($this->_removedEntities[$oid])) {
            $this->_removedEntities[$oid] = $entity;
        }
    }
    
    public function isRegisteredRemoved(Doctrine_Entity $entity)
    {
        return isset($this->_removedEntities[$entity->getOid()]);
    }

    /**
     * builds a flush tree that is used in transactions
     *
     * The returned array has all the initialized components in
     * 'correct' order. Basically this means that the records of those
     * components can be saved safely in the order specified by the returned array.
     *
     * @param array $tables     an array of Doctrine_Table objects or component names
     * @return array            an array of component names in flushing order
     */
    public function buildFlushTree(array $entityNames)
    {
        $tree = array();
        foreach ($entityNames as $k => $entity) {
            if ( ! ($mapper instanceof Doctrine_Mapper)) {
                $mapper = $this->conn->getMapper($mapper);
            }
            $nm = $mapper->getComponentName();

            $index = array_search($nm, $tree);

            if ($index === false) {
                $tree[] = $nm;
                $index  = max(array_keys($tree));
            }

            $rels = $mapper->getClassMetadata()->getRelations();

            // group relations

            foreach ($rels as $key => $rel) {
                if ($rel instanceof Doctrine_Relation_ForeignKey) {
                    unset($rels[$key]);
                    array_unshift($rels, $rel);
                }
            }

            foreach ($rels as $rel) {
                $name   = $rel->getTable()->getComponentName();
                $index2 = array_search($name, $tree);
                $type   = $rel->getType();

                // skip self-referenced relations
                if ($name === $nm) {
                    continue;
                }

                if ($rel instanceof Doctrine_Relation_ForeignKey) {
                    if ($index2 !== false) {
                        if ($index2 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree,$index2,0,$nm);
                        $index = $index2;
                    } else {
                        $tree[] = $name;
                    }
                } else if ($rel instanceof Doctrine_Relation_LocalKey) {
                    if ($index2 !== false) {
                        if ($index2 <= $index)
                            continue;

                        unset($tree[$index2]);
                        array_splice($tree, $index, 0, $name);
                    } else {
                        array_unshift($tree,$name);
                        $index++;
                    }
                } else if ($rel instanceof Doctrine_Relation_Association) {
                    $t = $rel->getAssociationFactory();
                    $n = $t->getComponentName();

                    if ($index2 !== false) {
                        unset($tree[$index2]);
                    }

                    array_splice($tree, $index, 0, $name);
                    $index++;

                    $index3 = array_search($n, $tree);

                    if ($index3 !== false) {
                        if ($index3 >= $index)
                            continue;

                        unset($tree[$index]);
                        array_splice($tree, $index3, 0, $n);
                        $index = $index2;
                    } else {
                        $tree[] = $n;
                    }
                }
            }
        }
        
        return $tree;
    }
    
    /**
     * persists all the pending records from all tables
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     * @deprecated
     */
    /*public function saveAll()
    {
        $this->conn->beginInternalTransaction();
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getMappers());
        
        $tree = array_combine($tree, array_fill(0, count($tree), array()));
        
        foreach ($this->_managedEntities as $oid => $entity) {
            $className = $entity->getClassName();
            $tree[$className][] = $entity;
        }
        
        // save all records
        foreach ($tree as $className => $entities) {
            $mapper = $this->conn->getMapper($className);
            foreach ($entities as $entity) {
                $mapper->saveSingleRecord($entity);
            }
        }
        
        // save all associations
        foreach ($tree as $className => $entities) {
            $mapper = $this->conn->getMapper($className);
            foreach ($entities as $entity) {
                $mapper->saveAssociations($entity);
            }
        }
        $this->conn->commit();
    }*/
    
    /**
     * Adds an entity to the pool of managed entities.
     * @deprecated
     */
    public function manage(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();
        if ( ! isset($this->_managedEntities[$oid])) {
            $this->_managedEntities[$oid] = $entity;
            return true;
        }
        return false;
    }
    
    /**
     * @param integer $oid                  object identifier
     * @return boolean                      whether ot not the operation was successful
     * @deprecated The new implementation of detach() should remove the entity
     *             from the identity map.
     */
    public function detach(Doctrine_Entity $entity)
    {
        $oid = $entity->getOid();
        if ( ! isset($this->_managedEntities[$oid])) {
            return false;
        }
        unset($this->_managedEntities[$oid]);
        return true;
    }
    
    /**
     * Detaches all currently managed entities.
     *
     * @return integer   The number of detached entities.
     * @todo Deprecated. The new implementation should remove all entities from
     *       the identity map.
     */
    public function detachAll()
    {
        $numDetached = count($this->_managedEntities);
        $this->_managedEntities = array();
        return $numDetached;
    }
    
    /**
     * Registers an entity in the identity map.
     * 
     * @return boolean  TRUE if the registration was successful, FALSE if the identity of
     *                  the entity in question is already managed.
     * @throws Doctrine_Connection_Exception  If the entity has no (database) identity.
     */
    public function registerIdentity(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->identifier());
        if ( ! $idHash) {
            throw new Doctrine_Connection_Exception("Entity with oid '" . $entity->getOid()
                    . "' has no identity and therefore can't be added to the identity map.");
        }
        $className = $entity->getClassMetadata()->getRootClassName();
        if (isset($this->_identityMap[$className][$idHash])) {
            return false;
        }
        $this->_identityMap[$className][$idHash] = $entity;
        return true;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $entityName
     * @todo unify with detachAll()
     */
    public function clearIdentitiesForEntity($entityName)
    {
        $this->_identityMap[$entityName] = array();
    }
    
    /**
     * Removes an entity from the identity map.
     *
     * @param Doctrine_Entity $entity
     * @return unknown
     * @todo This will be the new detach().
     */
    public function unregisterIdentity(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->identifier());
        if ( ! $idHash) {
            throw new Doctrine_Connection_Exception("Entity with oid '" . $entity->getOid()
                    . "' has no identity and therefore can't be removed from the identity map.");
        }
        $className = $entity->getClassMetadata()->getRootClassName();
        if (isset($this->_identityMap[$className][$idHash])) {
            unset($this->_identityMap[$className][$idHash]);
            return true;
        }

        return false;
    }
    
    /**
     * Finds an entity in the identity map by its identifier hash.
     *
     * @param unknown_type $idHash
     * @param unknown_type $rootClassName
     * @return unknown
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
     *
     * @param array $id
     * @return string
     */
    public function getIdentifierHash(array $id)
    {
        return implode(' ', $id);
    }
    
    /**
     * Checks whether an entity is registered in the identity map.
     *
     * @param Doctrine_Entity $entity
     * @return boolean
     */
    public function contains(Doctrine_Entity $entity)
    {
        $idHash = $this->getIdentifierHash($entity->identifier());
        if ( ! $idHash) {
            return false;
        }

        return isset($this->_identityMap
                [$entity->getClassMetadata()->getRootClassName()]
                [$idHash]);
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
    
}
