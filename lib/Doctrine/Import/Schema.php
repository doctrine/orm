<?php
/*
 * $Id: Schema.php 1838 2007-06-26 00:58:21Z nicobn $
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
 * class Doctrine_Import_Schema
 *
 * Different methods to import a XML schema. The logic behind using two different
 * methods is simple. Some people will like the idea of producing Doctrine_Record
 * objects directly, which is totally fine. But in fast and growing application,
 * table definitions tend to be a little bit more volatile. importArr() can be used
 * to output a table definition in a PHP file. This file can then be stored
 * independantly from the object itself.
 *
 * @package     Doctrine
 * @subpackage  Import
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1838 $
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Import_Schema
{
    public $relations = array();
    public $indexes = array();
    public $generateBaseClasses = false;
    
    /**
     * generateBaseClasses
     *
     * @param string $bool 
     * @return void
     */
    public function generateBaseClasses($bool = null)
    {
        if ($bool !== null) {
            $this->generateBaseClasses = $bool;
        }
        
        return $this->generateBaseClasses;
    }
    
    /**
     * buildSchema
     *
     * @param string $schema 
     * @param string $format 
     * @return void
     */
    public function buildSchema($schema, $format)
    {
        $array = array();
        
        foreach ((array) $schema AS $s) {
            if (is_file($s)) {
                $array = array_merge($array, $this->parseSchema($s, $format));
            } else if (is_dir($s)) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($s),
                                                      RecursiveIteratorIterator::LEAVES_ONLY);
            
                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    if (end($e) === $format) {
                        $array = array_merge($array, $this->parseSchema($file->getPathName(), $format));
                    }
                }
            }
        }
        
        $this->buildRelationships($array);
        
        return array('schema' => $array, 'relations' => $this->relations, 'indexes' => $this->indexes);
    }
    
    /**
     * importSchema
     *
     * A method to import a Schema and translate it into a Doctrine_Record object
     *
     * @param  string $schema       The file containing the XML schema
     * @param  string $directory    The directory where the Doctrine_Record class will be written
     * @param  array $models        Optional array of models to import
     *
     * @access public
     */
    public function importSchema($schema, $format = 'yml', $directory = null, $models = array())
    {
        $builder = new Doctrine_Import_Builder();
        $builder->setTargetPath($directory);
        $builder->generateBaseClasses($this->generateBaseClasses());
        
        $schema = $this->buildSchema($schema, $format);
        
        $array = $schema['schema'];
        
        foreach ($array as $name => $properties) {
            if (!empty($models) && !in_array($properties['className'], $models)) {
                continue;
            }
            
            $options = $this->getOptions($properties, $directory);
            $columns = $this->getColumns($properties);
            $relations = $this->getRelations($properties);
            
            $builder->buildRecord($options, $columns, $relations);
        }
    }
    
    /**
     * getOptions
     *
     * @param string $properties 
     * @param string $directory 
     * @return void
     */
    public function getOptions($properties, $directory)
    {
      $options = array();
      $options['className'] = $properties['className'];
      $options['fileName'] = $directory.DIRECTORY_SEPARATOR.$properties['className'].'.class.php';
      $options['tableName'] = isset($properties['tableName']) ? $properties['tableName']:null;
      
      if (isset($properties['inheritance'])) {
          $options['inheritance'] = $properties['inheritance'];
      }
      
      return $options;
    }
    
    /**
     * getColumns
     *
     * @param string $properties 
     * @return void
     */
    public function getColumns($properties)
    {
      return isset($properties['columns']) ? $properties['columns']:array();
    }
    
    /**
     * getRelations
     *
     * @param string $properties 
     * @return void
     */
    public function getRelations($properties)
    {
      return isset($this->relations[$properties['className']]) ? $this->relations[$properties['className']]:array();
    }

    public function getIndexes($properties)
    {
      return isset($properties['indexes']) ? $properties['indexes']:array();;
    }
    
    /**
     * parseSchema
     *
     * A method to parse a Yml Schema and translate it into a property array.
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the XML schema
     * @return array
     */
    public function parseSchema($schema, $type)
    {
        $array = Doctrine_Parser::load($schema, $type);
        
        $build = array();
        
        foreach ($array as $className => $table) {
            $columns = array();
            
            $className = isset($table['className']) ? (string) $table['className']:(string) $className;
            $tableName = isset($table['tableName']) ? (string) $table['tableName']:(string) Doctrine::tableize($className);
            
            $build[$className]['className'] = $className;
            
            if (isset($table['columns'])) {
                foreach ($table['columns'] as $columnName => $field) {
                    $colDesc = array();
                    $colDesc['name'] = isset($field['name']) ? (string) $field['name']:$columnName;
                    $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                    $colDesc['ptype'] = isset($field['ptype']) ? (string) $field['ptype']:(string) $colDesc['type'];
                    $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                    $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                    $colDesc['unsigned'] = isset($field['unsigned']) ? (bool) $field['unsigned']:null;
                    $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                    $colDesc['default'] = isset($field['default']) ? (string) $field['default']:null;
                    $colDesc['notnull'] = isset($field['notnull']) ? (bool) (isset($field['notnull']) && $field['notnull']):null;
                    $colDesc['autoincrement'] = isset($field['autoincrement']) ? (bool) (isset($field['autoincrement']) && $field['autoincrement']):null;
                    $colDesc['unique'] = isset($field['unique']) ? (bool) (isset($field['unique']) && $field['unique']):null;
                    $colDesc['values'] = isset($field['values']) ? (array) $field['values']: null;

                    $columns[(string) $colDesc['name']] = $colDesc;
                }
                
                $build[$className]['tableName'] = $tableName;
                $build[$className]['columns'] = $columns;
                $build[$className]['relations'] = isset($table['relations']) ? $table['relations']:array();
                $build[$className]['indexes'] = isset($table['indexes']) ? $table['indexes']:array();
            }
            
            if (isset($table['inheritance'])) {
                $build[$className]['inheritance'] = $table['inheritance'];
            }
        }
        
        return $build;
    }
    
    /**
     * buildRelationships
     *
     * @param string $array 
     * @return void
     */
    public function buildRelationships(&$array)
    {
        foreach ($array as $name => $properties) {
            if (!isset($properties['relations'])) {
                continue;
            }
            
            $className = $properties['className'];
            $relations = $properties['relations'];
            
            foreach ($relations as $alias => $relation) {
 
                $class = isset($relation['class']) ? $relation['class']:$alias;
                
                $relation['foreign'] = isset($relation['foreign'])?$relation['foreign']:'id';
                $relation['alias'] = isset($relation['alias']) ? $relation['alias'] : $alias;
                $relation['class'] = $class;
                
                if (isset($relation['type']) && $relation['type']) {
                    $relation['type'] = $relation['type'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                } else {
                    $relation['type'] = Doctrine_Relation::ONE;
                }

                if (isset($relation['foreignType']) && $relation['foreignType']) {
                    $relation['foreignType'] = $relation['foreignType'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                }
                
                if(isset($relation['refClass']) && !empty($relation['refClass'])  && (!isset($array[$relation['refClass']]['relations']) || empty($array[$relation['refClass']]['relations']))) {
                    $array[$relation['refClass']]['relations'][$className] = array('local'=>$relation['local'],'foreign'=>$relation['foreign'],'ignore'=>true);
                    $array[$relation['refClass']]['relations'][$relation['class']] = array('local'=>$relation['local'],'foreign'=>$relation['foreign'],'ignore'=>true);
                    
                    if(isset($relation['foreignAlias'])) {
                        $array[$relation['class']]['relations'][$relation['foreignAlias']] = array('type'=>$relation['type'],'local'=>$relation['foreign'],'foreign'=>$relation['local'],'refClass'=>$relation['refClass'],'class'=>$className);
                    }
                }
                
                $this->relations[$className][$alias] = $relation;
            }
        }
        
        $this->fixRelationships();
    }
    
    /**
     * fixRelationships
     *
     * @return void
     */
    public function fixRelationships()
    {
        // define both sides of the relationship
        foreach($this->relations as $className => $relations) {
            foreach ($relations AS $alias => $relation) {
                if(isset($relation['ignore']) && $relation['ignore'] || isset($relation['refClass']) || isset($this->relations[$relation['class']]['relations'][$className])) {
                    continue;
                }
                    
                $newRelation = array();
                $newRelation['foreign'] = $relation['local'];
                $newRelation['local'] = $relation['foreign'];
                $newRelation['class'] = $className;
                $newRelation['alias'] = isset($relation['foreignAlias'])?$relation['foreignAlias']:$className;
                
                if(isset($relation['foreignType'])) {
                    $newRelation['type'] = $relation['foreignType'];
                } else {
                    $newRelation['type'] = $relation['type'] === Doctrine_Relation::ONE ? Doctrine_Relation::MANY:Doctrine_Relation::ONE;
                }
                
                if( isset($this->relations[$relation['class']]) && is_array($this->relations[$relation['class']]) ) {
                    foreach($this->relations[$relation['class']] as $otherRelation) {
                        // skip fully defined m2m relationships
                        if(isset($otherRelation['refClass']) && $otherRelation['refClass'] == $className) {
                            continue(2);
                        }
                    }
                }
                
                $this->relations[$relation['class']][$className] = $newRelation;
            }
        }
    }
}