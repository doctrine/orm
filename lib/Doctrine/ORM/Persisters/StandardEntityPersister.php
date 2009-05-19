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

namespace Doctrine\ORM\Persisters;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Base class for all EntityPersisters.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.doctrine-project.org
 * @since       2.0
 */
class StandardEntityPersister
{    
    /**
     * Metadata object that describes the mapping of the mapped entity class.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $_class;
    
    /**
     * The name of the entity the persister is used for.
     * 
     * @var string
     */
    protected $_entityName;

    /**
     * The Connection instance.
     *
     * @var Doctrine\DBAL\Connection $conn
     */
    protected $_conn;
    
    /**
     * The EntityManager instance.
     *
     * @var Doctrine\ORM\EntityManager
     */
    protected $_em;

    /**
     * Queued inserts.
     *
     * @var array
     */
    protected $_queuedInserts = array();

    /**
     * Initializes a new instance of a class derived from AbstractEntityPersister
     * that uses the given EntityManager and persists instances of the class described
     * by the given class metadata descriptor.
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->_em = $em;
        $this->_entityName = $class->name;
        $this->_conn = $em->getConnection();
        $this->_class = $class;
    }
    
    /**
     * Inserts an entity.
     *
     * @param object $entity The entity to insert.
     * @return mixed If the entity uses a post-insert ID generator, the generated
     *               ID is returned, NULL otherwise.
     */
    public function insert($entity)
    {
        $insertData = array();
        $this->_prepareData($entity, $insertData, true);

        $stmt = $this->_conn->prepare($this->_class->insertSql);
        $stmt->execute(array_values($insertData));
        $stmt->closeCursor();

        $idGen = $this->_class->getIdGenerator();
        if ($idGen->isPostInsertGenerator()) {
            return $idGen->generate($this->_em, $entity);
        }
        return null;
    }

    /**
     * Adds an entity to the queued inserts.
     *
     * @param object $entity
     */
    public function addInsert($entity)
    {
        $this->_queuedInserts[] = $entity;
    }

    /**
     * Executes all queued inserts.
     */
    public function executeInserts()
    {
        if ( ! $this->_queuedInserts) {
            return;
        }
        
        $postInsertIds = array();
        $idGen = $this->_class->getIdGenerator();
        $isPostInsertId = $idGen->isPostInsertGenerator();

        $stmt = $this->_conn->prepare($this->_class->insertSql);
        foreach ($this->_queuedInserts as $entity) {
            $insertData = array();
            $this->_prepareData($entity, $insertData, true);
            $stmt->execute(array_values($insertData));
            if ($isPostInsertId) {
                $postInsertIds[$idGen->generate($this->_em, $entity)] = $entity;
            }
        }
        $stmt->closeCursor();
        $this->_queuedInserts = array();

        return $postInsertIds;
    }
    
    /**
     * Updates an entity.
     *
     * @param object $entity The entity to update.
     */
    public function update($entity)
    {
        $updateData = array();
        $this->_prepareData($entity, $updateData);
        $id = array_combine($this->_class->getIdentifierFieldNames(),
                $this->_em->getUnitOfWork()->getEntityIdentifier($entity));
        $this->_conn->update($this->_class->getTableName(), $updateData, $id);
    }
    
    /**
     * Deletes an entity.
     *
     * @param object $entity The entity to delete.
     */
    public function delete($entity)
    {
        $id = array_combine(
                $this->_class->getIdentifierFieldNames(),
                $this->_em->getUnitOfWork()->getEntityIdentifier($entity)
              );
        $this->_conn->delete($this->_class->getTableName(), $id);
    }

    /**
     * Adds an entity to delete.
     *
     * @param object $entity
     */
    public function addDelete($entity)
    {
        
    }

    /**
     * Executes all pending entity deletions.
     * 
     * @see addDelete()
     */
    public function executeDeletions()
    {
        
    }

    /**
     * Gets the ClassMetadata instance of the entity class this persister is used for.
     *
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->_class;
    }

    /**
     * Gets the table name to use for temporary identifier tables.
     */
    public function getTemporaryIdTableName()
    {
        //...
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomJoins()
    {
        return array();
    }
    
    /**
     * Callback that is invoked during the SQL construction process.
     */
    public function getCustomFields()
    {
        return array();
    }
    
    /**
     * Prepares the data of an entity for an insert/update operation.
     *
     * @param object $entity
     * @param array $result The reference to the data array.
     * @param boolean $isInsert
     */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        foreach ($this->_em->getUnitOfWork()->getEntityChangeSet($entity) as $field => $change) {
            $oldVal = $change[0];
            $newVal = $change[1];

            $columnName = $this->_class->getColumnName($field);

            if (isset($this->_class->associationMappings[$field])) {
                $assocMapping = $this->_class->associationMappings[$field];
                if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                    continue;
                }
                foreach ($assocMapping->sourceToTargetKeyColumns as $sourceColumn => $targetColumn) {
                    $otherClass = $this->_em->getClassMetadata($assocMapping->targetEntityName);
                    if ($newVal === null) {
                        $result[$sourceColumn] = null;
                    } else {
                        $result[$sourceColumn] = $otherClass->getReflectionProperty(
                            $otherClass->getFieldName($targetColumn))->getValue($newVal);
                    }
                }
            } else if ($newVal === null) {
                $result[$columnName] = null;
            } else {
                $result[$columnName] = Type::getType($this->_class->getTypeOfField($field))
                        ->convertToDatabaseValue($newVal, $this->_conn->getDatabasePlatform());
            }
        }
    }

    /**
     * Loads an entity by a list of field criteria.
     *
     * @param array $criteria The criteria by which to load the entity.
     * @param object $entity The entity to load the data into. If not specified,
     *                       a new entity is created.
     */
    public function load(array $criteria, $entity = null)
    {
        $stmt = $this->_conn->prepare($this->_getSelectSingleEntitySql($criteria));
        $stmt->execute(array_values($criteria));
        $data = array();
        foreach ($stmt->fetch(\PDO::FETCH_ASSOC) as $column => $value) {
            $fieldName = $this->_class->lcColumnToFieldNames[$column];
            $data[$fieldName] = Type::getType($this->_class->getTypeOfField($fieldName))
                    ->convertToPHPValue($value);
        }
        $stmt->closeCursor();

        if ($entity === null) {
            $entity = $this->_em->getUnitOfWork()->createEntity($this->_entityName, $data);
        } else {
            foreach ($data as $field => $value) {
                $this->_class->reflFields[$field]->setValue($entity, $value);
            }
            $id = array();
            if ($this->_class->isIdentifierComposite()) {
                foreach ($this->_class->identifier as $fieldName) {
                    $id[] = $data[$fieldName];
                }
            } else {
                $id = array($data[$this->_class->getSingleIdentifierFieldName()]);
            }
            $this->_em->getUnitOfWork()->registerManaged($entity, $id, $data);
        }

        if ( ! $this->_em->getConfiguration()->getAllowPartialObjects()) {
            foreach ($this->_class->associationMappings as $field => $assoc) {
                if ($assoc->isOneToOne()) {
                    if ($assoc->isLazilyFetched()) {
                        // Inject proxy
                        $proxy = $this->_em->getProxyGenerator()->getAssociationProxy($entity, $assoc);
                        $this->_class->reflFields[$field]->setValue($entity, $proxy);
                    } else {
                        //TODO: Eager fetch?
                    }
                } else {
                    // Inject collection
                    $this->_class->reflFields[$field]->setValue(
                            $entity, new PersistentCollection($this->_em,
                            $this->_em->getClassMetadata($assoc->targetEntityName)
                        ));
                }
            }
        }

        return $entity;
    }

    /**
     * Gets the SELECT SQL to select a single entity by a set of field criteria.
     *
     * @param array $criteria
     * @return string
     */
    protected function _getSelectSingleEntitySql(array $criteria)
    {
        $columnList = '';
        foreach ($this->_class->columnNames as $column) {
            if ($columnList != '') $columnList .= ', ';
            $columnList .= $column;
        }

        $conditionSql = '';
        foreach ($criteria as $field => $value) {
            if ($conditionSql != '') $conditionSql .= ' AND ';
            $conditionSql .= $this->_class->columnNames[$field] . ' = ?';
        }

        return 'SELECT ' . $columnList . ' FROM ' . $this->_class->getTableName()
                . ' WHERE ' . $conditionSql;
    }
}