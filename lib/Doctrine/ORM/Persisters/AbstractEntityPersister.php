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
        $id = array_combine($this->_classMetadata->getIdentifierFieldNames(),
                $this->_em->getUnitOfWork()->getEntityIdentifier($entity));
        $this->_conn->delete($this->_classMetadata->getTableName(), $id);
    }

    /**
     *
     * @return <type>
     */
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
        foreach ($this->_em->getUnitOfWork()->getEntityChangeSet($entity) as $field => $change) {
            if (is_array($change)) {
                list ($oldVal, $newVal) = each($change);
            } else {
                $oldVal = null;
                $newVal = $change;
            }
            
            $type = $this->_classMetadata->getTypeOfField($field);
            $columnName = $this->_classMetadata->getColumnName($field);

            if ($this->_classMetadata->hasAssociation($field)) {
                if ($newVal !== null) {
                    $assocMapping = $this->_classMetadata->getAssociationMapping($field);
                    if ( ! $assocMapping->isOneToOne() || $assocMapping->isInverseSide()) {
                        //echo "NOT TO-ONE OR INVERSE!";
                        continue;
                    }
                    foreach ($assocMapping->getSourceToTargetKeyColumns() as $sourceColumn => $targetColumn) {
                        //TODO: throw exc if field not set
                        $otherClass = $this->_em->getClassMetadata($assocMapping->getTargetEntityName());
                        $result[$sourceColumn] = $otherClass->getReflectionProperty(
                                $otherClass->getFieldName($targetColumn))->getValue($newVal);
                    }
                } else if ( ! $isInsert) {
                    echo "NO INSERT AND NEWVAL NULL ON 1-1 ASSOC, OWNING SIDE";
                }
            } else if (is_null($newVal)) {
                $result[$columnName] = null;
            } else {
                $result[$columnName] = $type->convertToDatabaseValue($newVal, $this->_conn->getDatabasePlatform());
            }
        }
        
        // Populate the discriminator column on insert in JOINED & SINGLE_TABLE inheritance
        if ($isInsert && ($this->_classMetadata->isInheritanceTypeJoined() ||
                $this->_classMetadata->isInheritanceTypeSingleTable())) {
            $discColumn = $this->_classMetadata->getDiscriminatorColumn();
            $discMap = $this->_classMetadata->getDiscriminatorMap();
            $result[$discColumn['name']] = array_search($this->_entityName, $discMap);
        }
    }
}
