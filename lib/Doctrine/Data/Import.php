<?php
/*
 *  $Id: Import.php 2552 2007-09-19 19:33:00Z Jonathan.Wage $
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
 * Doctrine_Data_Import
 *
 * @package     Doctrine
 * @package     Data
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2552 $
 */
class Doctrine_Data_Import extends Doctrine_Data
{
    protected $_importedObjects = array();
    protected $_rows = array();
    
    /**
     * constructor
     *
     * @param string $directory 
     * @return void
     */
    public function __construct($directory = null)
    {
        if ($directory !== null) {
            $this->setDirectory($directory);
        }
    }

    /**
     * doImport
     *
     * @return void
     */
    public function doImport()
    {
        $directory = $this->getDirectory();
        
        $array = array();
        
        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $e = explode('.', $dir);
                
                // If they specified a specific yml file
                if (end($e) == 'yml') {
                    $array = array_merge($array, Doctrine_Parser::load($dir, $this->getFormat()));
                // If they specified a directory
                } else if(is_dir($dir)) {
                    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                            RecursiveIteratorIterator::LEAVES_ONLY);

                    foreach ($it as $file) {
                        $e = explode('.', $file->getFileName());
                        if (in_array(end($e), $this->getFormats())) {
                            $array = array_merge($array, Doctrine_Parser::load($file->getPathName(), $this->getFormat()));
                        }
                    }   
                }
            }
        }
        
        $this->_loadData($array);
    }
    
    protected function _buildRows($className, $data)
    {
        foreach ($data as $rowKey => $row) {
            // do the same for the row information
            $this->_rows[$className][$rowKey] = $row;
            
            foreach ($row as $key => $value) {
                if (Doctrine::getTable($className)->hasRelation($key) && is_array($value)) {
                    $keys = array_keys($value);
                    
                    // Skip associative arrays defining keys to relationships
                    if (!isset($keys[0])) {
                        $this->_buildRows(Doctrine::getTable($className)->getRelation($key)->getTable()->getOption('name'), $value);
                    }
                }
            }
        }
    }
    
    protected function _buildNestedSetRows($className, $data)
    {
        foreach ($data as $rowKey => $row) {
            $children = isset($row['children']) ? $row['children']:array();
            unset($row['children']);
            $this->_rows[$className][$rowKey] = $row;
            
            $this->_buildNestedSetRows($className, $children);
        }
    }
    
    protected function _getImportedObject($rowKey)
    {
        if (isset($this->_importedObjects[$rowKey])) {
            return $this->_importedObjects[$rowKey];
        } else {
            throw new Doctrine_Data_Exception('Invalid row key specified: ' . $rowKey);
        }
    }
    
    protected function _processRow($rowKey, $row)
    {
        $obj = $this->_importedObjects[$rowKey];
        
        foreach ($row as $key => $value) {
            if ($obj->getTable()->hasColumn($key)) {
                $obj->set($key, $value);
            } else if (method_exists($obj, 'set' . Doctrine::classify($key))) {
                $func = 'set' . Doctrine::classify($key);
                $obj->$func($value);
            } else if ($obj->getTable()->hasRelation($key)) {
                if (is_array($value)) {
                    if (isset($value[0])) {
                        foreach ($value as $link) {
                            
                            if ($obj->getTable()->getRelation($key)->getType() === Doctrine_Relation::ONE) {
                                $obj->set($key, $this->_getImportedObject($link));
                            } else if ($obj->getTable()->getRelation($key)->getType() === Doctrine_Relation::MANY) {
                                $relation = $obj->$key;
                                
                                $relation[] = $this->_getImportedObject($link);
                            }
                        }
                    } else {
                        $obj->$key->fromArray($value);
                    }
                } else {
                    $obj->set($key, $this->_getImportedObject($value));
                }
            }
        }
    }
    
    /**
     * loadData
     *
     * @param string $array 
     * @return void
     */
    protected function _loadData(array $array)
    {
        $nestedSets = array();
        
        $specifiedModels = $this->getModels();
        $rows = array();
        
        foreach ($array as $className => $data) {
            
            if ( ! empty($specifiedModels) && !in_array($className, $specifiedModels)) {
                continue;
            }
            
            // This is simple here to get the templates present for this model
            // better way?
            $obj = new $className(null, true);
            $templates = array_keys($obj->getTable()->getTemplates());
            
            if (in_array('Doctrine_Template_NestedSet', $templates)) {
                $nestedSets[$className][] = $data;
                $this->_buildNestedSetRows($className, $data);
            } else {
                $this->_buildRows($className, $data);
            }
        }
        
        $buildRows = array();
        foreach ($this->_rows as $className => $classRows) {
            foreach ($classRows as $rowKey => $row) {
                $buildRows[$rowKey] = $row;
                $this->_importedObjects[$rowKey] = new $className();
            }
        }

        foreach($buildRows as $rowKey => $row) {
            $this->_processRow($rowKey, $row);
        }

        $objects = array();
        foreach ($this->_importedObjects as $object) {
            $className = get_class($object);
            $objects[$className] = $className;
        }

        $manager = Doctrine_Manager::getInstance();
        foreach ($manager as $connection) {            
            $tree = $connection->unitOfWork->buildFlushTree($objects);
            
            foreach ($tree as $model) {
                foreach ($this->_importedObjects as $obj) {
                    $templates = array_keys($obj->getTable()->getTemplates());
                    
                    if ($obj instanceof $model && !in_array('Doctrine_Template_NestedSet', $templates)) {
                        $obj->save();
                    }
                }
            }
        }
        
        foreach ($nestedSets as $className => $sets) {
            foreach ($sets as $data) {
                $this->_loadNestedSetData($className, $data);
            }
        }
    }
    
    protected function _loadNestedSetData($model, $nestedSetData, $parent = null)
    {
        $manager = Doctrine_Manager::getInstance();

        foreach($nestedSetData AS $rowKey => $nestedSet)
        {
            $children = array();
            $data  = array();
            
            if( array_key_exists('children', $nestedSet) )
            {
                $children = $nestedSet['children'];
                $children = array_reverse($children, true);
                unset($nestedSet['children']);
            }

            $record = $this->_importedObjects[$rowKey];
            
            if( !$parent )
            {
                $manager->getTable($model)->getTree()->createRoot($record);
            } else {
                $parent->getNode()->addChild($record);
            }

            if( is_array($children) AND !empty($children) )
            {
                $this->_loadNestedSetData($model, $children, $record);
            }
        }
    }

    /**
     * doImportDummyData
     *
     * @param string $num 
     * @return void
     */
    public function doImportDummyData($num = 3)
    {
        $models = Doctrine::getLoadedModels();
        
        $specifiedModels = $this->getModels();
        
        foreach ($models as $name) {
            if ( ! empty($specifiedModels) && !in_array($name, $specifiedModels)) {
                continue;
            }
            
            for ($i = 0; $i < $num; $i++) {
                $obj = new $name();
                
                $this->populateDummyRecord($obj);
                
                $obj->save();
                
                $ids[get_class($obj)][] = $obj->identifier();
            }
        }
    }
    
    public function populateDummyRecord(Doctrine_Record $record)
    {
        $lorem = explode(' ', "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem 
                               Ipsum has been the industry's standard dummy text ever since the 1500s, when an 
                               unknown printer took a galley of type and scrambled it to make a type specimen book. 
                               It has survived not only five centuries, but also the leap into electronic 
                               typesetting, remaining essentially unchanged. It was popularised in the 1960s with 
                               the release of Letraset sheets containing Lorem Ipsum passages, and more recently 
                               with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.");
        
        $columns = array_keys($record->toArray());
        $pks = $record->getTable()->getIdentifier();
        
        if ( ! is_array($pks)) {
          $pks = array($pks);
        }
        
        foreach ($columns as $column) {
            
            if ( ! in_array($column, $pks)) {
                if ($relation = $this->isRelation($record, $column)) {
                    $alias = $relation['alias'];
                    $relationObj = $record->$alias;
                    
                    $this->populateDummyRecord($relationObj);
                    
                } else {
                    
                    $definition = $record->getTable()->getDefinitionOf($column);
                    
                    switch($definition['type'])
                    {
                        case 'string';     
                            shuffle($lorem);
                    
                            $record->$column = substr(implode(' ', $lorem), 0, $definition['length']);
                        break;
                        
                        case 'integer':
                            $record->$column = rand();
                        break;
                        
                        case 'boolean':
                            $record->$column = true;
                        break;
                        
                        case 'float':
                            $record->$column = number_format(rand($definition['length'], $definition['length']), 2, '.', null);
                        break;
                        
                        case 'array':
                            $record->$column = array('test' => 'test');
                        break;
                        
                        case 'object':
                            $record->$column = new stdObject();
                        break;
                        
                        case 'blob':
                            $record->$column = '';
                        break;
                        
                        case 'clob':
                            $record->$column = '';
                        break;
                        
                        case 'timestamp':
                            $record->$column = date('Y-m-d h:i:s', time());
                        break;
                        
                        case 'time':
                            $record->$column = date('h:i:s', time());
                        break;
                        
                        case 'date':
                            $record->$column = date('Y-m-d', time());
                        break;
                        
                        case 'enum':
                            $record->$column = 'test';
                        break;
                        
                        case 'gzip':
                            $record->$column = 'test';
                        break;
                    }
                }
            }
        }
        
        return $record;
    }
}