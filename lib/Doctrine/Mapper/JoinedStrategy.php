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

/**
 * The joined mapping strategy maps a single entity instance to several tables in the
 * database as it is defined by <b>Class Table Inheritance</b>.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @package     Doctrine
 * @subpackage  DefaultStrategy
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
class Doctrine_Mapper_JoinedStrategy extends Doctrine_Mapper_Strategy
{
    protected $_columnNameFieldNameMap = array();
    
    /**
     * Inserts an entity that is part of a Class Table Inheritance hierarchy.
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     */
    public function doInsert(Doctrine_Record $record)
    {
        $class = $this->_mapper->getClassMetadata();
        $conn = $this->_mapper->getConnection();
                    
        $dataSet = $this->_groupFieldsByDefiningClass($record);
        $component = $class->getClassName();
        $classes = $class->getParentClasses();
        array_unshift($classes, $component);
        
        try {
            $conn->beginInternalTransaction();
            $identifier = null;
            foreach (array_reverse($classes) as $k => $parent) {
                $parentClass = $conn->getClassMetadata($parent);
                if ($k == 0) {
                    $identifierType = $parentClass->getIdentifierType();
                    if ($identifierType == Doctrine::IDENTIFIER_AUTOINC) {
                        $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
                        $identifier = $conn->sequence->lastInsertId();
                    } else if ($identifierType == Doctrine::IDENTIFIER_SEQUENCE) {
                        $seq = $record->getClassMetadata()->getTableOption('sequenceName');
                        if ( ! empty($seq)) {
                            $id = $conn->sequence->nextId($seq);
                            $identifierFields = (array)$parentClass->getIdentifier();
                            $dataSet[$parent][$identifierFields[0]] = $id;
                            $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
                        }
                    } else {
                        throw new Doctrine_Mapper_Exception("Unsupported identifier type '$identifierType'.");
                    }
                    $record->assignIdentifier($identifier);
                } else {
                    foreach ((array) $record->identifier() as $id => $value) {
                        $dataSet[$parent][$parentClass->getColumnName($id)] = $value;
                    }
                    $this->_insertRow($parentClass->getTableName(), $dataSet[$parent]);
                }
            }
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }
    
    /**
     * Updates an entity that is part of a Class Table Inheritance hierarchy.
     *
     * @param Doctrine_Record $record   record to be updated
     * @return boolean                  whether or not the update was successful
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    public function doUpdate(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        $classMetadata = $this->_mapper->getClassMetadata();
        $identifier = $this->_convertFieldToColumnNames($record->identifier(), $classMetadata);
        $dataSet = $this->_groupFieldsByDefiningClass($record);
        $component = $classMetadata->getClassName();
        $classes = $classMetadata->getParentClasses();
        array_unshift($classes, $component);

        foreach ($record as $field => $value) {
            if ($value instanceof Doctrine_Record) {
                if ( ! $value->exists()) {
                    $value->save();
                }
                $record->set($field, $value->getIncremented());
            }
        }

        foreach (array_reverse($classes) as $class) {
            $parentTable = $conn->getClassMetadata($class);
            $this->_updateRow($parentTable->getTableName(), $dataSet[$class], $identifier);
        }
        
        $record->assignIdentifier(true);

        return true;
    }
    
    /**
     * Deletes an entity that is part of a Class Table Inheritance hierarchy.
     *
     */
    public function doDelete(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        try {
            $class = $this->_mapper->getClassMetadata();
            $conn->beginInternalTransaction();
            $this->deleteComposites($record);

            $record->state(Doctrine_Record::STATE_TDIRTY);

            $identifier = $this->_convertFieldToColumnNames($record->identifier(), $class);

            foreach ($class->getParentClasses() as $parent) {
                $parentClass = $conn->getClassMetadata($parent);
                $this->_deleteRow($parentClass->getTableName(), $identifier);
            }

            $conn->delete($class->getTableName(), $identifier);
            $record->state(Doctrine_Record::STATE_TCLEAN);

            $this->_mapper->removeRecord($record);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }
    
    /**
     * Adds all parent classes as INNER JOINs and subclasses as OUTER JOINs
     * to the query.
     *
     * Callback that is invoked during the SQL construction process.
     *
     * @return array  The custom joins in the format <className> => <joinType>
     */
    public function getCustomJoins()
    {
        $customJoins = array();
        $classMetadata = $this->_mapper->getClassMetadata();
        foreach ($classMetadata->getParentClasses() as $parentClass) {
            $customJoins[$parentClass] = 'INNER';
        }
        foreach ((array)$classMetadata->getSubclasses() as $subClass) {
            if ($subClass != $this->_mapper->getComponentName()) {
                $customJoins[$subClass] = 'LEFT';
            }
        }
        
        return $customJoins;
    }
    
    /**
     * Adds the discriminator column to the selected fields in a query as well as
     * all fields of subclasses. In Class Table Inheritance the default behavior is that
     * all subclasses are joined in through OUTER JOINs when querying a base class.
     *
     * Callback that is invoked during the SQL construction process.
     *
     * @return array  An array with the field names that will get added to the query.
     */
    public function getCustomFields()
    {
        $classMetadata = $this->_mapper->getClassMetadata();
        $conn = $this->_mapper->getConnection();
        $fields = array($classMetadata->getInheritanceOption('discriminatorColumn'));
        if ($classMetadata->getSubclasses()) {
            foreach ($classMetadata->getSubclasses() as $subClass) {
                $fields = array_merge($conn->getMetadata($subClass)->getFieldNames(), $fields);
            }
        }
        
        return array_unique($fields);
    }
    
    /**
     *
     */
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        
        $fieldNames = $this->_mapper->getClassMetadata()->getFieldNames();
        $this->_fieldNames = array_unique($fieldNames);
        
        return $fieldNames;
    }
    
    /**
     * 
     */
    public function getFieldName($columnName)
    {
        if (isset($this->_columnNameFieldNameMap[$columnName])) {
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        $classMetadata = $this->_mapper->getClassMetadata();
        $conn = $this->_mapper->getConnection();
        
        if ($classMetadata->hasColumn($columnName)) {
            $this->_columnNameFieldNameMap[$columnName] = $classMetadata->getFieldName($columnName);
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        foreach ($classMetadata->getParentClasses() as $parentClass) {
            $parentTable = $conn->getClassMetadata($parentClass);
            if ($parentTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $parentTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }
        
        foreach ((array)$classMetadata->getSubclasses() as $subClass) {
            $subTable = $conn->getClassMetadata($subClass);
            if ($subTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $subTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }

        throw new Doctrine_Mapper_Exception("No field name found for column name '$columnName'.");
    }
    
    /**
     * 
     * @todo Looks like this better belongs into the ClassMetadata class.
     */
    public function getOwningTable($fieldName)
    {
        $conn = $this->_mapper->getConnection();
        $classMetadata = $this->_mapper->getClassMetadata();
        if ($classMetadata->hasField($fieldName) && ! $classMetadata->isInheritedField($fieldName)) {
            return $classMetadata;
        }
        
        foreach ($classMetadata->getParentClasses() as $parentClass) {
            $parentTable = $conn->getClassMetadata($parentClass);
            if ($parentTable->hasField($fieldName) && ! $parentTable->isInheritedField($fieldName)) {
                return $parentTable;
            }
        }
        
        foreach ((array)$classMetadata->getSubclasses() as $subClass) {
            $subTable = $conn->getClassMetadata($subClass);
            if ($subTable->hasField($fieldName) && ! $subTable->isInheritedField($fieldName)) {
                return $subTable;
            }
        }
        
        throw new Doctrine_Mapper_Exception("Unable to find owner of field '$fieldName'.");
    }
    
    /**
     * Analyzes the fields of the entity and creates a map in which the field names
     * are grouped by the class names they belong to. 
     *
     */
    protected function _groupFieldsByDefiningClass(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        $classMetadata = $this->_mapper->getClassMetadata();
        $dataSet = array();
        $component = $classMetadata->getClassName();
        $array = $record->getPrepared();
        
        $classes = array_merge(array($component), $classMetadata->getParentClasses());
        
        foreach ($classes as $class) {
            $dataSet[$class] = array();            
            $parentClassMetadata = $conn->getClassMetadata($class);
            foreach ($parentClassMetadata->getColumns() as $columnName => $definition) {
                if ((isset($definition['primary']) && $definition['primary'] === true) ||
                        (isset($definition['inherited']) && $definition['inherited'] === true)) {
                    continue;
                }
                $fieldName = $classMetadata->getFieldName($columnName);
                if ( ! array_key_exists($fieldName, $array)) {
                    continue;
                }
                $dataSet[$class][$columnName] = $array[$fieldName];
            }
        }
        
        return $dataSet;
    }
}

