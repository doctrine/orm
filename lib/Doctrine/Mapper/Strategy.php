<?php 

abstract class Doctrine_Mapper_Strategy
{
    protected $_mapper;
    
    /**
     * The names of all the fields that are available on entities created by this mapper. 
     */
    protected $_fieldNames = array();
    
    public function __construct(Doctrine_Mapper $mapper)
    {
        $this->_mapper = $mapper;
    }
    
    /**
     * Assumes that the keys of the given field array are field names and converts
     * them to column names.
     *
     * @return array
     */
    protected function _convertFieldToColumnNames(array $fields, Doctrine_ClassMetadata $class)
    {
        $converted = array();
        foreach ($fields as $fieldName => $value) {
            $converted[$class->getColumnName($fieldName)] = $value;
        }
        
        return $converted;
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
    
    public function getFieldName($columnName)
    {
        return $this->_mapper->getClassMetadata()->getFieldName($columnName);
    }
    
    public function getFieldNames()
    {
        if ($this->_fieldNames) {
            return $this->_fieldNames;
        }
        $this->_fieldNames = $this->_mapper->getClassMetadata()->getFieldNames();
        return $this->_fieldNames;
    }
    
    public function getOwningTable($fieldName)
    {
        return $this->_mapper->getClassMetadata();
    }
    
    abstract public function doDelete(Doctrine_Record $record);
    abstract public function doInsert(Doctrine_Record $record);
    abstract public function doUpdate(Doctrine_Record $record);
    
    /**
     * Inserts a row into a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _insertRow($tableName, array $data)
    {
        $this->_mapper->getConnection()->insert($tableName, $data);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _deleteRow($tableName, array $identifierToMatch)
    {
        $this->_mapper->getConnection()->delete($tableName, $identifierToMatch);
    }
    
    /**
     * Deletes rows of a table.
     *
     * @todo This method could be used to allow mapping to secondary table(s).
     * @see http://www.oracle.com/technology/products/ias/toplink/jpa/resources/toplink-jpa-annotations.html#SecondaryTable
     */
    protected function _updateRow($tableName, array $data, array $identifierToMatch)
    {
        $this->_mapper->getConnection()->update($tableName, $data, $identifierToMatch);
    }
    
}