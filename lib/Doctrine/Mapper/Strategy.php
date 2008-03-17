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
 * Base class for all mapping strategies used by mappers.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @package     Doctrine
 * @subpackage  Strategy
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.org
 * @since       1.0
 */
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
    
    public function getOwningClass($fieldName)
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