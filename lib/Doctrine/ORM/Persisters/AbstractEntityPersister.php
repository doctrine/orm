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

use Doctrine\ORM\EntityManager;
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
abstract class AbstractEntityPersister
{    
    /**
     * Metadata object that describes the mapping of the mapped entity class.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $_classMetadata;
    
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
    public function __construct(EntityManager $em, ClassMetadata $classMetadata)
    {
        $this->_em = $em;
        $this->_entityName = $classMetadata->getClassName();
        $this->_conn = $em->getConnection();
        $this->_classMetadata = $classMetadata;
    }
    
    /**
     * Inserts an entity.
     *
     * @param object $entity The entity to insert.
     * @return mixed
     */
    public function insert($entity)
    {
        $insertData = array();
        $this->_prepareData($entity, $insertData, true);
        $this->_conn->insert($this->_classMetadata->getTableName(), $insertData);
        $idGen = $this->_classMetadata->getIdGenerator();
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
        $insertData = array();
        $this->_prepareData($entity, $insertData, true);
        $this->_queuedInserts[] = $insertData;
    }

    /**
     * Executes all queued inserts.
     */
    public function executeInserts()
    {
        $tableName = $this->_classMetadata->getTableName();
        $stmt = $this->_conn->prepare($this->_classMetadata->getInsertSql());
        foreach ($this->_queuedInserts as $insertData) {
            $stmt->execute(array_values($insertData));
        }
        $this->_queuedInserts = array();
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
        $id = array_combine($this->_classMetadata->getIdentifierFieldNames(),
                $this->_em->getUnitOfWork()->getEntityIdentifier($entity));
        $this->_conn->update($this->_classMetadata->getTableName(), $updateData, $id);
    }
    
    /**
     * Deletes an entity.
     *
     * @param object $entity The entity to delete.
     */
    public function delete($entity)
    {
        $id = array_combine(
                $this->_classMetadata->getIdentifierFieldNames(),
                $this->_em->getUnitOfWork()->getEntityIdentifier($entity)
              );
        $this->_conn->delete($this->_classMetadata->getTableName(), $id);
    }

    public function addDelete($entity)
    {
        
    }

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
        return $this->_classMetadata;
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
     * Gets all field mappings of the entire entity hierarchy.
     *
     * @return array
     */
    public function getAllFieldMappingsInHierarchy()
    {
        return $this->_classMetadata->getFieldMappings();
    }
    
    /**
     * Prepares the data of an entity for an insert/update operation.
     *
     * @param object $entity
     * @param array $array
     * @param boolean $isInsert
     */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        foreach ($this->_em->getUnitOfWork()->getEntityChangeSet($entity) as $field => $change) {
            $oldVal = $change[0];
            $newVal = $change[1];
            
            $type = $this->_classMetadata->getTypeOfField($field);
            $columnName = $this->_classMetadata->getColumnName($field);

            if ($this->_classMetadata->hasAssociation($field)) {
                $assocMapping = $this->_classMetadata->getAssociationMapping($field);
                if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                    continue;
                }
                foreach ($assocMapping->getSourceToTargetKeyColumns() as $sourceColumn => $targetColumn) {
                    $otherClass = $this->_em->getClassMetadata($assocMapping->getTargetEntityName());
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
                $result[$columnName] = $type->convertToDatabaseValue($newVal, $this->_conn->getDatabasePlatform());
            }
        }
    }
}