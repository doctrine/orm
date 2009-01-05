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

#namespace Doctrine\ORM\Persisters;

/**
 * Base class for all EntityPersisters.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 3406 $
 * @link        www.phpdoctrine.org
 * @since       2.0
 * @todo Rename to AbstractEntityPersister
 */
abstract class Doctrine_ORM_Persisters_AbstractEntityPersister
{
    /**
     * The names of all the fields that are available on entities. 
     */
    protected $_fieldNames = array();
    
    /**
     * Metadata object that descibes the mapping of the mapped entity class.
     *
     * @var Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $_classMetadata;
    
    /**
     * The name of the Entity the persister is used for.
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
     * Null object.
     */
    //private $_nullObject;

    /**
     * Initializes a new instance of a class derived from AbstractEntityPersister
     * that uses the given EntityManager and persists instances of the class described
     * by the given class metadata descriptor.
     */
    public function __construct(Doctrine_ORM_EntityManager $em, Doctrine_ORM_Mapping_ClassMetadata $classMetadata)
    {
        $this->_em = $em;
        $this->_entityName = $classMetadata->getClassName();
        $this->_conn = $em->getConnection();
        $this->_classMetadata = $classMetadata;
    }
    
    /**
     * Inserts an entity.
     *
     * @param Doctrine\ORM\Entity $entity The entity to insert.
     * @return mixed
     */
    public function insert($entity)
    {
        $insertData = array();
        $this->_prepareData($entity, $insertData, true);
        $this->_conn->insert($this->_classMetadata->getTableName(), $insertData);
        $idGen = $this->_em->getIdGenerator($this->_classMetadata->getClassName());
        if ($idGen->isPostInsertGenerator()) {
            return $idGen->generate($entity);
        }
        return null;
    }
    
    /**
     * Updates an entity.
     *
     * @param Doctrine\ORM\Entity $entity The entity to update.
     * @return void
     */
    public function update(Doctrine_ORM_Entity $entity)
    {
        /*$dataChangeSet = $entity->_getDataChangeSet();
        $referenceChangeSet = $entity->_getReferenceChangeSet();
        
        foreach ($referenceChangeSet as $field => $change) {
            $assocMapping = $entity->getClass()->getAssociationMapping($field);
            if ($assocMapping instanceof Doctrine_Association_OneToOneMapping) {
                if ($assocMapping->isInverseSide()) {
                    continue; // ignore inverse side
                }
                // ... null out the foreign key
                
            }
            //...
        }
        */
        //TODO: perform update
    }
    
    /**
     * Deletes an entity.
     *
     * @param Doctrine\ORM\Entity $entity The entity to delete.
     * @return void
     */
    public function delete(Doctrine_ORM_Entity $entity)
    {
        //TODO: perform delete
    }
    
    /**
     * Inserts a row into a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _insertRow($tableName, array $data)
    {
        $this->_conn->insert($tableName, $data);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _deleteRow($tableName, array $identifierToMatch)
    {
        $this->_conn->delete($tableName, $identifierToMatch);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _updateRow($tableName, array $data, array $identifierToMatch)
    {
        $this->_conn->update($tableName, $data, $identifierToMatch);
    }
    
    public function getClassMetadata()
    {
        return $this->_classMetadata;
    }
    
    /**
     * @todo Move to ClassMetadata?
     */
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        $this->_fieldNames = $this->_classMetadata->getFieldNames();
        return $this->_fieldNames;
    }

    /**
     * Gets the name of the class in the entity hierarchy that owns the field with
     * the given name. The owning class is the one that defines the field.
     *
     * @param string $fieldName
     * @return string
     * @todo Consider using 'inherited' => 'ClassName' to make the lookup simpler.
     */
    public function getOwningClass($fieldName)
    {
        if ($this->_classMetadata->isInheritanceTypeNone()) {
            return $this->_classMetadata;
        } else {
            foreach ($this->_classMetadata->getParentClasses() as $parentClass) {
                $parentClassMetadata = Doctrine_ORM_Mapping_ClassMetadataFactory::getInstance()
                        ->getMetadataFor($parentClass);
                if ( ! $parentClassMetadata->isInheritedField($fieldName)) {
                    return $parentClassMetadata;
                }
            }
        }
        throw new Doctrine_Exception("Unable to find defining class of field '$fieldName'.");
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
     * Prepares all the entity data for insertion into the database.
     *
     * @param array $array
     * @return void
     */
    protected function _prepareData($entity, array &$result, $isInsert = false)
    {
        foreach ($this->_em->getUnitOfWork()->getDataChangeSet($entity) as $field => $change) {
            list ($oldVal, $newVal) = each($change);
            $type = $this->_classMetadata->getTypeOfField($field);
            $columnName = $this->_classMetadata->getColumnName($field);

            if (is_null($newVal)) {
                $result[$columnName] = null;
            } else if (is_object($newVal)) {
                $assocMapping = $this->_classMetadata->getAssociationMapping($field);
                if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                    //echo "NOT TO-ONE OR INVERSE!";
                    continue;
                }
                foreach ($assocMapping->getSourceToTargetKeyColumns() as $sourceColumn => $targetColumn) {
                    //TODO: What if both join columns (local/foreign) are just db-only
                    // columns (no fields in models) ? Currently we assume the foreign column
                    // is mapped to a field in the foreign entity.
                    //TODO: throw exc if field not set
                    $otherClass = $this->_em->getClassMetadata($assocMapping->getTargetEntityName());
                    $result[$sourceColumn] = $otherClass->getReflectionProperty(
                            $otherClass->getFieldName($targetColumn))->getValue($newVal);
                }
            } else {
                switch ($type) {
                    case 'array':
                    case 'object':
                        $result[$columnName] = serialize($newVal);
                        break;
                    case 'gzip':
                        $result[$columnName] = gzcompress($newVal, 5);
                        break;
                    case 'boolean':
                        $result[$columnName] = $this->_em->getConnection()->convertBooleans($newVal);
                    break;
                    default:
                        $result[$columnName] = $newVal;
                }
            }
            /*$result[$columnName] = $type->convertToDatabaseValue(
                    $newVal, $this->_em->getConnection()->getDatabasePlatform());*/
        }
        
        // Populate the discriminator column on insert in Single & Class Table Inheritance
        if ($isInsert && ($this->_classMetadata->isInheritanceTypeJoined() ||
                $this->_classMetadata->isInheritanceTypeSingleTable())) {
            $discColumn = $this->_classMetadata->getInheritanceOption('discriminatorColumn');
            $discMap = $this->_classMetadata->getInheritanceOption('discriminatorMap');
            $result[$discColumn] = array_search($this->_entityName, $discMap);
        }
    }
    
    abstract protected function _doUpdate(Doctrine_ORM_Entity $entity);
    abstract protected function _doInsert(Doctrine_ORM_Entity $entity);
}
