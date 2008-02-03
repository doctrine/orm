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
        $table = $this->_classMetadata;
                    
        $dataSet = $this->_formatDataSet($record);
        $component = $table->getClassName();

        $classes = $table->getOption('parents');
        array_unshift($classes, $component);
        
        try {
            $this->_conn->beginInternalTransaction();
            $identifier = null;
            foreach (array_reverse($classes) as $k => $parent) {
                $parentTable = $this->_conn->getMetadata($parent);
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
        $table = $this->_classMetadata;
        $identifier = $record->identifier();                     
        $dataSet = $this->_formatDataSet($record);
        $component = $table->getClassName();
        $classes = $table->getOption('parents');
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
            $parentTable = $this->_conn->getMetadata($class);
            $this->_conn->update($parentTable, $dataSet[$class], $identifier);
        }
        
        $record->assignIdentifier(true);

        return true;
    }
    

    protected function _doDelete(Doctrine_Record $record, Doctrine_Connection $conn)
    {
        try {
            $table = $this->_classMetadata;
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
        foreach ($this->_classMetadata->getOption('parents') as $parentClass) {
            $customJoins[$parentClass] = 'INNER';
        }
        foreach ((array)$this->_classMetadata->getOption('subclasses') as $subClass) {
            if ($subClass != $this->_domainClassName) {
                $customJoins[$subClass] = 'LEFT';
            }
        }
        return $customJoins;
    }
    
    public function getCustomFields()
    {
        $fields = array($this->_classMetadata->getInheritanceOption('discriminatorColumn'));
        //$fields = array();
        if ($this->_classMetadata->getOption('subclasses')) {
            foreach ($this->_classMetadata->getOption('subclasses') as $subClass) {
                $fields = array_merge($this->_conn->getMetadata($subClass)->getFieldNames(), $fields);
            }
        }
        return array_unique($fields);
    }
    
    /**
     *
     */
    public function getDiscriminatorColumn()
    {
        $joinedParents = $this->_classMetadata->getOption('joinedParents');
        if (count($joinedParents) <= 0) {
            $inheritanceMap = $this->_classMetadata->getOption('inheritanceMap');
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
        
        $fieldNames = $this->_classMetadata->getFieldNames();
        $this->_fieldNames = array_unique($fieldNames);
        
        return $fieldNames;
    }
    
    public function getFieldName($columnName)
    {
        if (isset($this->_columnNameFieldNameMap[$columnName])) {
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        if ($this->_classMetadata->hasColumn($columnName)) {
            $this->_columnNameFieldNameMap[$columnName] = $this->_classMetadata->getFieldName($columnName);
            return $this->_columnNameFieldNameMap[$columnName];
        }
        
        foreach ($this->_classMetadata->getOption('parents') as $parentClass) {
            $parentTable = $this->_conn->getMetadata($parentClass);
            if ($parentTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $parentTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }
        
        foreach ((array)$this->_classMetadata->getOption('subclasses') as $subClass) {
            $subTable = $this->_conn->getMetadata($subClass);
            if ($subTable->hasColumn($columnName)) {
                $this->_columnNameFieldNameMap[$columnName] = $subTable->getFieldName($columnName);
                return $this->_columnNameFieldNameMap[$columnName];
            }
        }

        throw new Doctrine_Mapper_Exception("No field name found for column name '$columnName'.");
    }
    
    public function getOwningTable($fieldName)
    {        
        if ($this->_classMetadata->hasField($fieldName) && ! $this->_classMetadata->isInheritedField($fieldName)) {
            return $this->_classMetadata;
        }
        
        foreach ($this->_classMetadata->getOption('parents') as $parentClass) {
            $parentTable = $this->_conn->getMetadata($parentClass);
            if ($parentTable->hasField($fieldName) && ! $parentTable->isInheritedField($fieldName)) {
                return $parentTable;
            }
        }
        
        foreach ((array)$this->_classMetadata->getOption('subclasses') as $subClass) {
            $subTable = $this->_conn->getMetadata($subClass);
            if ($subTable->hasField($fieldName) && ! $subTable->isInheritedField($fieldName)) {
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
        $table = $this->_classMetadata;
        $dataSet = array();
        $component = $table->getClassName();
        $array = $record->getPrepared();
        
        $classes = array_merge(array($component), $this->_classMetadata->getParentClasses());
        
        foreach ($classes as $class) {
            $metadata = $this->_conn->getMetadata($class);
            foreach ($metadata->getColumns() as $columnName => $definition) {
                if ((isset($definition['primary']) && $definition['primary'] === true) ||
                        (isset($definition['inherited']) && $definition['inherited'] === true)) {
                    continue;
                }
                $fieldName = $table->getFieldName($columnName);
                $dataSet[$class][$fieldName] = isset($array[$fieldName]) ? $array[$fieldName] : null;
            }
        }
        
        return $dataSet;
    }
}

