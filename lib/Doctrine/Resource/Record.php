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
class Doctrine_Resource_Record extends Doctrine_Record_Abstract implements Countable, IteratorAggregate
{
    protected $_data = array();
    protected $_model = null;
    protected $_schema = null;
    
    public function __construct($model, $loadRelations = true)
    {
        $this->_model = $model;
        
        $schema = $this->getConfig('schema');
        
        if (isset($schema['schema'][$model]) && $schema['schema'][$model]) {
            $this->_schema = $schema['schema'][$model];
        }
        
        if (isset($schema['relations'][$model]) && $schema['relations'][$model]) {
            $this->_schema['relations'] = $schema['relations'][$model];
        }
        
        $this->initialize($loadRelations);
    }
    
    public function initialize($loadRelations = true)
    {
        if (!$this->_schema) {
            return false;
        }
        
        $schema = $this->_schema;
        $relations = $this->_schema['relations'];
        
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
    
    public function get($get)
    {
        if (!isset($this->_data[$get])) {
            $this->_data[$get] = null;
        } else {
            $this->_data[$get] = Doctrine_Resource_Client::getInstance()->newRecord($get, false);
        }
        
        return $this->_data[$get];
    }

    public function set($set, $value)
    {
        $this->_data[$set] = $value;
    }
    
    public function count()
    {
        return count($this->_data);
    }
    
    public function getIterator()
    {
        return new ArrayIterator($this->_data);
    }
    
    public function save()
    {
        $format = $this->getConfig('format') ? $this->getConfig('format'):'xml';
        
        $request = new Doctrine_Resource_Request();
        $request->set('format', $format);
        $request->set('type', 'save');
        $request->set('model', $this->getModel());
        $request->set('data', $this->toArray());
        
        $response = $request->execute();
        
        $array = Doctrine_Parser::load($response, $format);
        
        $this->_data = $request->hydrate(array($array), $this->_model)->getFirst()->_data;
    }
    
    public function getModel()
    {
        return $this->_model;
    }
    
    public function toArray()
    {
        $array = array();
        
        foreach ($this->_data as $key => $value) {
            if ($value instanceof Doctrine_Resource_Collection OR $value instanceof Doctrine_Resource_Record) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
}
