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
 * Doctrine_Resource_Record
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Record extends Doctrine_Resource_Access implements Countable, IteratorAggregate
{
    protected $_data = array();
    protected $_model = null;
    protected $_table = null;
    protected $_changes = array();
    
    public function __construct($model, $loadRelations = true)
    {
        $this->_model = $model;
        $this->_table = Doctrine_Resource_Client::getInstance()->getTable($model);
        
        $this->initialize($loadRelations);
    }
    
    public function clearChanges()
    {
        $this->_changes = array();
    }
    
    public function initialize($loadRelations = true)
    {
        $schema = $this->getTable()->getSchema();
        $relations = $schema['relations'];
        
        if (isset($schema['columns'])) {
            $columns = $schema['columns'];
        
            foreach ($columns as $column) {
                if (!isset($this->_data[$column['name']]) || $this->_data[$column['name']]) {
                    $this->_data[$column['name']] = null;
                }
            }
        }
        
        if (isset($schema['relations']) && $loadRelations) {
            $relations = $schema['relations'];
            
            foreach ($relations as $relation) {
                if ($relation['type'] === Doctrine_Relation::ONE) {
                    $this->_data[$relation['alias']] = Doctrine_Resource_Client::getInstance()->newRecord($relation['class'], false); 
                } else {
                    $this->_data[$relation['alias']] = Doctrine_Resource_Client::getInstance()->newCollection($relation['class']);
                }
            }
        }
    }
    
    public function getConfig($key = null)
    {
        return Doctrine_Resource_Client::getInstance()->getConfig($key);
    }
    
    public function get($key)
    {
        return $this->_data[$key];
    }

    public function set($key, $value)
    {
        if ($this->_data[$key] != $value) {
            $this->_changes[$key] = $value;
        }
        
        $this->_data[$key] = $value;
    }
    
    public function count()
    {
        return count($this->_data);
    }
    
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }
    
    public function getChanges()
    {
        $array = array();
        
        foreach ($this->_data as $key => $value) {
            if ($this->getTable()->hasRelation($key)) {
                
                $relation = $this->getTable()->getRelation($key);
                
                if ($relation['type'] === Doctrine_Relation::ONE) {
                    if ($this->_data[$key]->hasChanges()) {
                        $array[$key] = $this->_data[$key]->getChanges();
                    }
                } else {
                    foreach ($this->_data[$key] as $key2 => $record) {
                        if ($record->hasChanges()) {
                            $array[$key][$record->getModel() . '_' .$key2] = $record->getChanges();
                        }
                    }
                }
            } else if ($this->getTable()->hasColumn($key)) {
                if (isset($this->_changes[$key])) {
                    $array[$key] = $value;
                }
            }
        }
        
        $identifier = $this->identifier();
        
        $array = array_merge($identifier, $array);
        
        return $array;
    }
    
    public function hasChanges()
    {
        return !empty($this->_changes) ? true:false;
    }
    
    public function save()
    {
        $format = $this->getConfig('format');
        
        $request = new Doctrine_Resource_Request();
        $request->set('format', $format);
        $request->set('type', 'save');
        $request->set('model', $this->getModel());
        $request->set('data', $this->getChanges());
        $request->set('identifier', $this->identifier());
        
        $response = $request->execute();
        
        $array = Doctrine_Parser::load($response, $format);
        
        $this->_data = $request->hydrate(array($array), $this->_model)->getFirst()->_data;
    }
    
    public function delete()
    {
        $format = $this->getConfig('format');
        
        $request = new Doctrine_Resource_Request();
        $request->set('format', $format);
        $request->set('type', 'delete');
        $request->set('model', $this->getModel());
        $request->set('identifier', $this->identifier());
        
        $response = $request->execute();
    }
    
    public function getTable()
    {
        return $this->_table;
    }
    
    public function getModel()
    {
        return $this->_model;
    }
    
    public function identifier()
    {
        $identifier = array();
        
        $schema = $this->getTable()->getSchema();
        $columns = $schema['columns'];
        
        if (isset($columns) && is_array($columns)) {
            foreach ($columns as $name => $column) {
                if ($column['primary'] == true) {
                    $identifier[$name] = $this->_data[$name];
                }
            }
        }
        
        return $identifier;
    }
    
    public function exists()
    {
        $identifier = $this->identifier();
        
        foreach ($identifier as $key => $value) {
            if (!$value) {
                return false;
            }
        }
        
        return true;
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->_data as $key => $value) {
            
            if ($this->getTable()->hasRelation($key) && $value instanceof Doctrine_Resource_Collection) {
                if ($value->count() > 0) {
                    $array[$key] = $value->toArray();
                }
            } else if ($this->getTable()->hasRelation($key) && $value instanceof Doctrine_Resource_Record) {
                if ($value->exists() || $value->hasChanges()) {
                    $array[$key] = $value->toArray();
                }
            } else if (!$this->getTable()->hasRelation($key) && $this->getTable()->hasColumn($key)) {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
