<?php

/**
 * The default strategy maps a single instance to a single database table, as is
 * the case in Single Table Inheritance & Concrete Table Inheritance.
 *
 * @since 1.0
 */
class Doctrine_Mapper_DefaultStrategy extends Doctrine_Mapper_Strategy
{
    
    /**
     * Deletes an entity.
     */
    public function doDelete(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        $metadata = $this->_mapper->getClassMetadata();
        try {
            $conn->beginInternalTransaction();
            $this->_deleteComposites($record);

            $record->state(Doctrine_Record::STATE_TDIRTY);
            
            $identifier = $this->_convertFieldToColumnNames($record->identifier(), $metadata);
            $this->_deleteRow($metadata->getTableName(), $identifier);
            $record->state(Doctrine_Record::STATE_TCLEAN);

            $this->_mapper->removeRecord($record);
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    
    /**
     * deletes all related composites
     * this method is always called internally when a record is deleted
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    protected function _deleteComposites(Doctrine_Record $record)
    {
        $classMetadata = $this->_mapper->getClassMetadata();
        foreach ($classMetadata->getRelations() as $fk) {
            if ($fk->isComposite()) {
                $obj = $record->get($fk->getAlias());
                if ($obj instanceof Doctrine_Record && 
                        $obj->state() != Doctrine_Record::STATE_LOCKED)  {
                    $obj->delete($this->_mapper->getConnection());
                }
            }
        }
    }
    
    /**
     * Inserts a single entity into the database, without any related entities.
     *
     * @param Doctrine_Record $record   The entity to insert.
     */
    public function doInsert(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        
        $fields = $record->getPrepared();
        if (empty($fields)) {
            return false;
        }
        
        //$class = $record->getClassMetadata();
        $class = $this->_mapper->getClassMetadata();
        $identifier = (array) $class->getIdentifier();
        $fields = $this->_convertFieldToColumnNames($fields, $class);

        $seq = $class->getTableOption('sequenceName');
        if ( ! empty($seq)) {
            $id = $conn->sequence->nextId($seq);
            $seqName = $identifier[0];
            $fields[$seqName] = $id;
            $record->assignIdentifier($id);
        }
        
        
        //echo $class->getTableName() . "--" . $class->getClassName() . '---' . get_class($record) . "<br/>";
        $this->_insertRow($class->getTableName(), $fields);

        if (empty($seq) && count($identifier) == 1 &&
                $class->getIdentifierType() != Doctrine::IDENTIFIER_NATURAL) {
            if (strtolower($conn->getName()) == 'pgsql') {
                $seq = $class->getTableName() . '_' . $identifier[0];
            }

            $id = $conn->sequence->lastInsertId($seq);

            if ( ! $id) {
                throw new Doctrine_Mapper_Exception("Couldn't get last insert identifier.");
            }

            $record->assignIdentifier($id);
        } else {
            $record->assignIdentifier(true);
        }
    }
    
    /**
     * Updates an entity.
     */
    public function doUpdate(Doctrine_Record $record)
    {
        $conn = $this->_mapper->getConnection();
        $classMetadata = $this->_mapper->getClassMetadata();
        $identifier = $this->_convertFieldToColumnNames($record->identifier(), $classMetadata);
        $data = $this->_convertFieldToColumnNames($record->getPrepared(), $classMetadata);
        $this->_updateRow($classMetadata->getTableName(), $data, $identifier);
        $record->assignIdentifier(true);
    }
}