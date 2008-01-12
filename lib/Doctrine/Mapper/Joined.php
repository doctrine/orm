<?php 

class Doctrine_Mapper_Joined extends Doctrine_Mapper_Abstract
{
    protected $_columnNameFieldNameMap = array();
    
    /**
     * inserts a record into database
     *
     * @param Doctrine_Record $record   record to be inserted
     * @return boolean
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    protected function _doInsert(Doctrine_Record $record)
    {
        $table = $this->_table;
                    
        $dataSet = $this->_formatDataSet($record);
        $component = $table->getComponentName();

        $classes = $table->getOption('joinedParents');
        array_unshift($classes, $component);
        
        try {
            $this->_conn->beginInternalTransaction();
            $identifier = null;
            foreach (array_reverse($classes) as $k => $parent) {
                $parentTable = $this->_conn->getTable($parent);
                if ($k == 0) {
                    $identifierType = $parentTable->getIdentifierType();
                    if ($identifierType == Doctrine::IDENTIFIER_AUTOINC) {
                        $this->_conn->insert($parentTable, $dataSet[$parent]);
                        $identifier = $this->_conn->sequence->lastInsertId();
                    } else if ($identifierType == Doctrine::IDENTIFIER_SEQUENCE) {
                        $seq = $record->getTable()->getOption('sequenceName');
                        if ( ! empty($seq)) {
                            $identifier = $this->_conn->sequence->nextId($seq);
                            $dataSet[$parent][$parentTable->getIdentifier()] = $identifier;
                            $this->_conn->insert($parentTable, $dataSet[$parent]);
                        }
                    } else {
                        throw new Doctrine_Mapper_Exception("Unsupported identifier type '$identifierType'.");
                    }
                    $record->assignIdentifier($identifier);
                } else {
                    foreach ((array) $record->identifier() as $id => $value) {
                        $dataSet[$parent][$id] = $value;
                    }
                    $this->_conn->insert($parentTable, $dataSet[$parent]);
                }
            }
            $this->_conn->commit();
        } catch (Exception $e) {
            $this->_conn->rollback();
            throw $e;
        }

        return true;
    }
    
    /**
     * updates given record
     *
     * @param Doctrine_Record $record   record to be updated
     * @return boolean                  whether or not the update was successful
     * @todo Move to Doctrine_Table (which will become Doctrine_Mapper).
     */
    protected function _doUpdate(Doctrine_Record $record)
    {
        $table = $this->_table;
        $identifier = $record->identifier();                     
        $dataSet = $this->_formatDataSet($record);
        $component = $table->getComponentName();
        $classes = $table->getOption('joinedParents');
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
            $parentTable = $this->_conn->getTable($class);
            $this->_conn->update($parentTable, $dataSet[$class], $identifier);
        }
        
        $record->assignIdentifier(true);

        return true;
    }
    

    protected function _doDelete(Doctrine_Record $record, Doctrine_Connection $conn)
    {
        try {
            $table = $this->_table;
            $conn->beginInternalTransaction();
            $this->deleteComposites($record);

            $record->state(Doctrine_Record::STATE_TDIRTY);

            foreach ($table->getOption('joinedParents') as $parent) {
                $parentTable = $conn->getTable($parent);
                $conn->delete($parentTable, $record->identifier());
            }

            $conn->delete($table, $record->identifier());
            $record->state(Doctrine_Record::STATE_TCLEAN);

            $this->removeRecord($record);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return true;
    }
    
    /**
     *
     *
     */
    public function getCustomJoins()
    {
        $customJoins = array();
        foreach ($this->_table->getOption('joinedParents') as $parentClass) {
            $customJoins[$parentClass] = 'INNER';
        }
        foreach ((array)$this->_table->getOption('subclasses') as $subClass) {
            if ($subClass != $this->_domainClassName) {
                $customJoins[$subClass] = 'LEFT';
            }
        }
        return $customJoins;
    }
    
    public function getCustomFields()
    {
        $fields = array();
        if ($this->_table->getOption('subclasses')) {
            foreach ($this->_table->getOption('subclasses') as $subClass) {
                $fields = array_merge($this->_conn->getTable($subClass)->getFieldNames(), $fields);
            }
        }
        return array_unique($fields);
    }
    
    /**
     *
     */
    public function getDiscriminatorColumn()
    {
        $joinedParents = $this->_table->getOption('joinedParents');
        if (count($joinedParents) <= 0) {
            $inheritanceMap = $this->_table->getOption('inheritanceMap');
        } else {
            $inheritanceMap = $this->_conn->getTable(array_pop($joinedParents))->getOption('inheritanceMap');
        }
        return isset($inheritanceMap[$this->_domainClassName]) ? $inheritanceMap[$this->_domainClassName] : array();
    }
    
    /**
     *
     */
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        
        $fieldNames = $this->_table->getFieldNames();
        foreach ($this->_table->getOption('joinedParents') as $parent) {
            $parentTable = $this->_conn->getTable($parent);
            $fieldNames = array_merge($parentTable->getFieldNames(), $fieldNames);
        }
        $this->_fieldNames = array_unique($fieldNames);
        
        return $fieldNames;
    }
    
    public function getFieldName($columnName)
    {
        if (isset($this->_columnNameFieldNameMap[$columnName])) {
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        if ($this->_table->hasColumn($columnName)) {
            $this->_columnNameFieldNameMap[$columnName] = $this->_table->getFieldName($columnName);
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        foreach ($this->_table->getOption('joinedParents') as $parentClass) {
            $parentTable = $this->_conn->getTable($parentClass);
            if ($parentTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $parentTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }
        
        foreach ((array)$this->_table->getOption('subclasses') as $subClass) {
            $subTable = $this->_conn->getTable($subClass);
            if ($subTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $subTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }
        
        throw new Doctrine_Mapper_Exception("No field name found for column name '$columnName'.");
    }
    
    public function getOwningTable($fieldName)
    {
        if ($this->_table->hasField($fieldName)) {
            return $this->_table;
        }
        
        foreach ($this->_table->getOption('joinedParents') as $parentClass) {
            $parentTable = $this->_conn->getTable($parentClass);
            if ($parentTable->hasField($fieldName)) {
                return $parentTable;
            }
        }
        
        foreach ((array)$this->_table->getOption('subclasses') as $subClass) {
            $subTable = $this->_conn->getTable($subClass);
            if ($subTable->hasField($fieldName)) {
                return $subTable;
            }
        }
        
        throw new Doctrine_Mapper_Exception("Unable to find owner of field '$fieldName'.");
    }
    
    /**
     * 
     */
    protected function _formatDataSet(Doctrine_Record $record)
    {
        $table = $this->_table;
        $dataSet = array();
        $component = $table->getComponentName();
        $array = $record->getPrepared();
        
        $classes = array_merge(array($component), $this->_table->getOption('joinedParents'));
        
        foreach ($classes as $class) {
            $table = $this->_conn->getTable($class);
            foreach ($table->getColumns() as $columnName => $definition) {
                if (isset($definition['primary'])) {
                    continue;
                }
                $fieldName = $table->getFieldName($columnName);
                $dataSet[$class][$fieldName] = isset($array[$fieldName]) ? $array[$fieldName] : null;
            }
        }
        
        return $dataSet;
    }
}

