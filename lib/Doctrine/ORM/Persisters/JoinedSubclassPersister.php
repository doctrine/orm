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

use Doctrine\Common\DoctrineException;

/**
 * The joined subclass persister maps a single entity instance to several tables in the
 * database as it is defined by <tt>Class Table Inheritance</tt>.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.doctrine-project.org
 * @since       2.0
 * @todo Reimplement.
 */
class JoinedSubclassPersister extends AbstractEntityPersister
{    
    /**
     * Inserts an entity that is part of a Class Table Inheritance hierarchy.
     *
     * @param object $record   record to be inserted
     * @return boolean
     * @override
     */
    /*public function insert($entity)
    {
        $class = $entity->getClass();
        
        $dataSet = array();
        
        $this->_prepareData($entity, $dataSet, true);
        
        $dataSet = $this->_groupFieldsByDefiningClass($class, $dataSet);
        
        $component = $class->name;
        $classes = $class->parentClasses;
        array_unshift($classes, $component);
        
        $identifier = null;
        foreach (array_reverse($classes) as $k => $parent) {
            $parentClass = $this->_em->getClassMetadata($parent);
            if ($k == 0) {
                if ($parentClass->isIdGeneratorIdentity()) {
                    $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
                    $identifier = $this->_conn->lastInsertId();
                } else if ($parentClass->isIdGeneratorSequence()) {
                    $seq = $entity->getClassMetadata()->getTableOption('sequenceName');
                    if ( ! empty($seq)) {
                        $id = $this->_conn->getSequenceManager()->nextId($seq);
                        $identifierFields = $parentClass->identifier;
                        $dataSet[$parent][$identifierFields[0]] = $id;
                        $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
                    }
                } else {
                    throw DoctrineException::updateMe("Unsupported identifier type '$identifierType'.");
                }
                $entity->_assignIdentifier($identifier);
            } else {
                foreach ($entity->_identifier() as $id => $value) {
                    $dataSet[$parent][$parentClass->getColumnName($id)] = $value;
                }
                $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
            }
        }

        return true;
    }*/
    
    /**
     * Updates an entity that is part of a Class Table Inheritance hierarchy.
     *
     * @param Doctrine_Entity $record   record to be updated
     * @return boolean                  whether or not the update was successful
     */
    /*protected function _doUpdate($entity)
    {
        $conn = $this->_conn;
        $classMetadata = $this->_classMetadata;
        $identifier = $this->_convertFieldToColumnNames($record->identifier(), $classMetadata);
        $dataSet = $this->_groupFieldsByDefiningClass($record);
        $component = $classMetadata->name;
        $classes = $classMetadata->parentClasses;
        array_unshift($classes, $component);

        foreach ($record as $field => $value) {
            if ($value instanceof Doctrine_ORM_Entity) {
                if ( ! $value->exists()) {
                    $value->save();
                }
                $idValues = $value->identifier();
                $record->set($field, $idValues[0]);
            }
        }

        foreach (array_reverse($classes) as $class) {
            $parentTable = $conn->getClassMetadata($class);
            $this->_updateRow($parentTable->getTableName(), $dataSet[$class], $identifier);
        }
        
        $record->assignIdentifier(true);

        return true;
    }*/
    
    /**
     * Deletes an entity that is part of a Class Table Inheritance hierarchy.
     *
     */
    /*protected function _doDelete(Doctrine_ORM_Entity $record)
    {
        $conn = $this->_conn;
        try {
            $class = $this->_classMetadata;
            $conn->beginInternalTransaction();
            $this->_deleteComposites($record);

            $record->_state(Doctrine_ORM_Entity::STATE_TDIRTY);

            $identifier = $this->_convertFieldToColumnNames($record->identifier(), $class);
            
            // run deletions, starting from the class, upwards the hierarchy
            $conn->delete($class->getTableName(), $identifier);
            foreach ($class->parentClasses as $parent) {
                $parentClass = $conn->getClassMetadata($parent);
                $this->_deleteRow($parentClass->getTableName(), $identifier);
            }
            
            $record->_state(Doctrine_ORM_Entity::STATE_TCLEAN);

            $this->removeRecord($record); // @todo should be done in the unitofwork
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }*/
    
    /**
     * Adds all parent classes as INNER JOINs and subclasses as OUTER JOINs
     * to the query.
     *
     * Callback that is invoked during the SQL construction process.
     *
     * @return array  The custom joins in the format <className> => <joinType>
     */
    /*public function getCustomJoins()
    {
        $customJoins = array();
        $classMetadata = $this->_classMetadata;
        foreach ($classMetadata->parentClasses as $parentClass) {
            $customJoins[$parentClass] = 'INNER';
        }
        foreach ($classMetadata->subClasses as $subClass) {
            if ($subClass != $this->getComponentName()) {
                $customJoins[$subClass] = 'LEFT';
            }
        }
        
        return $customJoins;
    }*/
    
    /**
     * Adds the discriminator column to the selected fields in a query as well as
     * all fields of subclasses. In Class Table Inheritance the default behavior is that
     * all subclasses are joined in through OUTER JOINs when querying a base class.
     *
     * Callback that is invoked during the SQL construction process.
     *
     * @return array  An array with the field names that will get added to the query.
     */
    /*public function getCustomFields()
    {
        $classMetadata = $this->_classMetadata;
        $conn = $this->_conn;
        $discrColumn = $classMetadata->discriminatorColumn;
        $fields = array($discrColumn['name']);
        if ($classMetadata->subClasses) {
            foreach ($classMetadata->subClasses as $subClass) {
                $fields = array_merge($conn->getClassMetadata($subClass)->fieldNames, $fields);
            }
        }
        return array_unique($fields);
    }*/
    
    /**
     *
     */
    /*public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        
        $fieldNames = $this->_classMetadata->fieldNames;
        $this->_fieldNames = array_unique($fieldNames);
        
        return $fieldNames;
    }*/
    
    /**
     * 
     * @todo Looks like this better belongs into the ClassMetadata class.
     */
    /*public function getOwningClass($fieldName)
    {
        $conn = $this->_conn;
        $classMetadata = $this->_classMetadata;
        if ($classMetadata->hasField($fieldName) && ! $classMetadata->isInheritedField($fieldName)) {
            return $classMetadata;
        }
        
        foreach ($classMetadata->parentClasses as $parentClass) {
            $parentTable = $conn->getClassMetadata($parentClass);
            if ($parentTable->hasField($fieldName) && ! $parentTable->isInheritedField($fieldName)) {
                return $parentTable;
            }
        }
        
        foreach ((array)$classMetadata->subClasses as $subClass) {
            $subTable = $conn->getClassMetadata($subClass);
            if ($subTable->hasField($fieldName) && ! $subTable->isInheritedField($fieldName)) {
                return $subTable;
            }
        }
        
        throw \Doctrine\Common\DoctrineException::updateMe("Unable to find defining class of field '$fieldName'.");
    }*/
    
    /**
     * Analyzes the fields of the entity and creates a map in which the field names
     * are grouped by the class names they belong to. 
     *
     * @return array
     */
    /*protected function _groupFieldsByDefiningClass(Doctrine_ClassMetadata $class, array $fields)
    {
        $dataSet = array();
        $component = $class->name;
        
        $classes = array_merge(array($component), $class->parentClasses);
        
        foreach ($classes as $class) {
            $dataSet[$class] = array();            
            $parentClassMetadata = $this->_em->getClassMetadata($class);
            foreach ($parentClassMetadata->fieldMappings as $fieldName => $mapping) {
                if ((isset($mapping['id']) && $mapping['id'] === true) ||
                        (isset($mapping['inherited']) && $mapping['inherited'] === true)) {
                    continue;
                }
                if ( ! array_key_exists($fieldName, $fields)) {
                    continue;
                }
                $columnName = $parentClassMetadata->getColumnName($fieldName);
                $dataSet[$class][$columnName] = $fields[$fieldName];
            }
        }
        
        return $dataSet;
    }*/
}