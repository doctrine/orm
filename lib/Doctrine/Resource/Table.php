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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Resource_Table
 *
 * @package     Doctrine
 * @subpackage  Resource
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Table
{
    protected $_model = null;
    protected $_schema = null;
    
    public function __construct($model)
    {
        $this->_model = $model;
        
        $schema = $this->getConfig('schema');
        
        if (isset($schema['schema'][$model]) && $schema['schema'][$model]) {
            $this->_schema = $schema['schema'][$model];
        }
        
        if (isset($schema['relations'][$model]) && $schema['relations'][$model]) {
            $this->_schema['relations'] = $schema['relations'][$model];
        }
    }
    
    public function getSchema()
    {
        return $this->_schema;
    }
    
    public function getRelations()
    {
        return $this->_schema['relations'];
    }
    
    public function getColumns()
    {
        return $this->_schema['columns'];
    }
    
    public function getConfig($key = null)
    {
        return Doctrine_Resource_Client::getInstance()->getConfig($key);
    }
    
    public function find($pk)
    {
        $model = $this->_model;
        
        $record = new $model();
        
        $pk = is_array($pk) ? $pk:array($pk);
        $identifier = $record->identifier();
        $identifier = is_array($identifier) ? $identifier:array($identifier);
        
        $where = '';
        foreach (array_keys($identifier) as $key => $name) {
            $value = $pk[$key];
            $where .= $model.'.' . $name . ' = '.$value;
        }
        
        $query = new Doctrine_Resource_Query();
        $query->from($model)->where($where)->limit(1);
        
        $result = $query->execute();
        
        return $result->getFirst();
    }
    
    public function hasColumn($name)
    {
        return isset($this->_schema['columns'][$name]) ? true:false;
    }
    
    public function getColumn($name)
    {
        if ($this->hasColumn($name)) {
            return $this->_columns[$name];
        }
    }
    
    public function hasRelation($name)
    {
        return isset($this->_schema['relations'][$name]) ? true:false;
    }
    
    public function getRelation($name)
    {
        if ($this->hasRelation($name)) {
            return $this->_schema['relations'][$name];
        }
    }
    
    public function getIdentifier()
    {
        $identifier = array();
        
        $schema = $this->getSchema();
        $columns = $schema['columns'];
        
        if (isset($columns) && is_array($columns)) {
            foreach ($columns as $name => $column) {
                if ($column['primary'] == true) {
                    $identifier[$name] = $name;
                }
            }
        }
        
        return $identifier;
    }
}