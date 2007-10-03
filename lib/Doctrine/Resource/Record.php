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
 * @package     Doctrine
 * @subpackage  Resource
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Resource_Record extends Doctrine_Resource_Access implements Countable, IteratorAggregate
{
    protected $_data = array();
    protected $_model = null;
    protected $_changes = array();
    
    public function __construct($model)
    {
        $this->_model = $model;
        
        $this->initialize();
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
    }
    
    public function getConfig($key = null)
    {
        return Doctrine_Resource_Client::getInstance()->getConfig($key);
    }
    
    public function get($key)
    {
        if (!isset($key)) {
            return;
        }
        
        if (!isset($this->_data[$key]) && $this->getTable()->hasRelation($key)) {
            $this->_data[$key] = $this->createRelation($key);
        }
        
        if (!array_key_exists($key, $this->_data)) {
            throw new Doctrine_Resource_Exception('Unknown property / related component: '.$key);
        }
        
        return $this->_data[$key];
    }

    public function set($key, $value)
    {
        if (!isset($key)) {
            return;
        }
        
        if (!isset($this->_data[$key]) && $this->getTable()->hasRelation($key)) {
            $this->_data[$key] = $this->createRelation($key);
        }
        
        if (!array_key_exists($key, $this->_data)) {
            throw new Doctrine_Resource_Exception('Unknown property / related component: '.$key);
        }
        
        if ($this->_data[$key] != $value && !$value instanceof Doctrine_Resource_Record && !$value instanceof Doctrine_Resource_Collection) {
            $this->_changes[$key] = $value;
        }
        
        $this->_data[$key] = $value;
    }
    
    public function createRelation($key)
    {
        $relation = $this->getTable()->getRelation($key);
        $class = $relation['class'];
        
        if ($relation['type'] === Doctrine_Relation::ONE) {
            $return = new $class();
        } else {
            $return = new Doctrine_Resource_Collection($class);
        }
        
        return $return;
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
        global $gotten;
        
        if (!$gotten) {
            $gotten = array();
        }
        
        $md5Hash = $this->getMd5Hash();
        
        if (!in_array($md5Hash, $gotten)) {
            $gotten[] = $md5Hash;
        }
        
        $array = array();
        
        foreach ($this->_data as $key => $value) {
            if ($this->getTable()->hasRelation($key)) {
                
                $relation = $this->getTable()->getRelation($key);
                
                if ($value instanceof Doctrine_Resource_Record) {
                    if ($value->hasChanges() && !in_array($value->getMd5Hash(), $gotten)) {
                        $array[$key] = $value->getChanges();
                    }
                } else if($value instanceof Doctrine_Resource_Collection) {
                    foreach ($value as $key2 => $record) {
                        if ($record->hasChanges() && !in_array($record->getMd5Hash(), $gotten)) {
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
        $request = new Doctrine_Resource_Request();
        $request->set('action', 'save');
        $request->set('model', $this->getModel());
        $request->set('identifier', $this->identifier());
        $request->set('data', $this->getChanges());
        
        $response = $request->execute();
        
        $this->fromArray($response);
    }
    
    public function delete()
    {
        $request = new Doctrine_Resource_Request();
        $request->set('action', 'delete');
        $request->set('model', $this->getModel());
        $request->set('identifier', $this->identifier());
        
        $response = $request->execute();
    }
    
    public function getTable()
    {
        $model = $this->_model;
        
        return Doctrine_Resource_Client::getInstance()->getTable($model);
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
    
    public function toArray($deep = false)
    {
        $data = array();
        
        foreach ($this->_data as $key => $value) {
            if ($deep && $this->getTable()->hasRelation($key)) {
                $data[$key] = $this->$key->toArray($deep);
            } else if ($this->getTable()->hasColumn($key)) {
                $data[$key]  = $value;
            }
        }
        
        return $data;
    }
    
    public function fromArray(array $array)
    {
        foreach ($array as $key => $value) {
            if ($this->getTable()->hasRelation($key) && is_array($value)) {
                $this->$key->fromArray($value);
            } else if ($this->getTable()->hasColumn($key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function getMd5Hash()
    {
        return md5(serialize($this->_data));
    }
}