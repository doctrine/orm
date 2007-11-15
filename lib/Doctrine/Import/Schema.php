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
    protected $_relations = array();
    protected $_options = array('packagesPrefix'        =>  'Package',
                                'packagesPath'          =>  '',
                                'generateBaseClasses'   =>  true,
                                'generateTableClasses'  =>  true,
                                'baseClassesDirectory'  =>  'generated',
                                'baseClassName'         =>  'Doctrine_Record',
                                'suffix'                =>  '.php');
    
    /**
     * getOption
     *
     * @param string $name 
     * @return void
     */
    public function getOption($name)
    {
        if (isset($this->_options[$name]))   {
            return $this->_options[$name];
        }
    }
    
    /**
     * setOption
     *
     * @param string $name 
     * @param string $value 
     * @return void
     */
    public function setOption($name, $value)
    {
        if (isset($this->_options[$name])) {
            $this->_options[$name] = $value;
        }
    }
    
    /**
     * setOptions
     *
     * @param string $options 
     * @return void
     */
    public function setOptions($options)
    {
        if (!empty($options)) {
          $this->_options = $options;
        }
    }

    /**
     * buildSchema
     *
     * Loop throug directories of schema files and part them all in to one complete array of schema information
     *
     * @param  string   $schema Array of schema files or single schema file. Array of directories with schema files or single directory
     * @param  string   $format Format of the files we are parsing and building from
     * @return array    $array
     */
    public function buildSchema($schema, $format)
    {
        $array = array();

        foreach ((array) $schema AS $s) {
            if (is_file($s) || is_string($s)) {
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

        $this->_buildRelationships($array);

        return $array;
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
     * @return void
     */
    public function importSchema($schema, $format = 'yml', $directory = null, $models = array())
    {
        $builder = new Doctrine_Import_Builder();
        $builder->setTargetPath($directory);
        
        foreach ($this->_options as $key => $value) {
            if ($value) {
                $builder->setOption($key, $value);
            }
        }
        
        $array = $this->buildSchema($schema, $format);

        foreach ($array as $name => $properties) {
            if ( ! empty($models) && !in_array($properties['className'], $models)) {
                continue;
            }
            
            $options = $this->getOptions($properties);
            $columns = $this->getColumns($properties);
            $relations = $this->getRelations($properties);
            $indexes = $this->getIndexes($properties);
            $attributes = $this->getAttributes($properties);
            $templates = $this->getTemplates($properties);
            $actAs = $this->getActAs($properties);
            $tableOptions = $this->getTableOptions($properties);
            
            $builder->buildRecord($options, $columns, $relations, $indexes, $attributes, $templates, $actAs, $tableOptions);
        }
    }

    /**
     * getOptions
     *
     * @param string $properties Array of table properties
     * @param string $directory  Directory we are writing the class to
     * @return array $options    Array of options from a parse schemas properties
     */
    public function getOptions($properties)
    {
        $options = array();
        $options['className'] = $properties['className'];
        $options['tableName'] = isset($properties['tableName']) ? $properties['tableName']:null;
        $options['connection'] = isset($properties['connection']) ? $properties['connection']:null;
        $options['connectionClassName'] = isset($properties['connection']) ? $properties['className']:null;
        $options['package'] = $properties['package'];
        
        if (isset($properties['inheritance'])) {
            $options['inheritance'] = $properties['inheritance'];
        }

        return $options;
    }

    /**
     * getColumns
     *
     * Get array of columns from table properties
     *
     * @param  string $properties Array of table properties
     * @return array  $columns    Array of columns
     */
    public function getColumns($properties)
    {
        return isset($properties['columns']) ? $properties['columns']:array();
    }

    /**
     * getRelations
     * 
     * Get array of relations from table properties
     *
     * @param  string $properties Array of tables properties
     * @return array  $relations  Array of relations
     */
    public function getRelations($properties)
    {
        $all_relations = isset($this->_relations[$properties['className']]) ? $this->_relations[$properties['className']]:array();
        
        // This is for checking for duplicates between alias-relations and a auto-generated relations to ensure the result set of unique relations
        $exist_relations = array();
        $unique_relations = array();
        foreach ($all_relations as $relation) {
            if (!in_array($relation['class'], $exist_relations)) {
                $exist_relations[] = $relation['class'];
                $unique_relations = array_merge($unique_relations, array($relation['alias'] => $relation));
            } else {
                // check to see if this relationship is not autogenerated, if it's not, then the user must have explicitly declared it
                if (!isset($relation['autogenerated']) || $relation['autogenerated'] != true) {
                    $unique_relations = array_merge($unique_relations, array($relation['alias'] => $relation));
                }
            }
        }
        
        return $unique_relations;
    }

    /**
     * getIndexes
     *
     * Get array of indexes from table properties
     *
     * @param  string $properties Array of table properties
     * @return array  $index
     */
    public function getIndexes($properties)
    {
        return isset($properties['indexes']) ? $properties['indexes']:array();;
    }

    /**
     * getAttributes
     *
     * Get array of attributes from table properties
     *
     * @param  string $properties Array of tables properties 
     * @return array  $attributes
     */
    public function getAttributes($properties)
    {
        return isset($properties['attributes']) ? $properties['attributes']:array();
    }

    /**
     * getTemplates
     *
     * Get array of templates from table properties
     *
     * @param  string $properties Array of table properties
     * @return array  $templates  Array of table templates
     */
    public function getTemplates($properties)
    {
        return isset($properties['templates']) ? $properties['templates']:array();
    }

    /**
     * getActAs
     *
     * Get array of actAs definitions from table properties
     *
     * @param  string $properties Array of table properties
     * @return array  $actAs      Array of actAs definitions from table properties
     */
    public function getActAs($properties)
    {
        return isset($properties['actAs']) ? $properties['actAs']:array();
    }
    
    /**
     * getTableOptions
     *
     * @param string $properties 
     * @return void
     */
    public function getTableOptions($properties)
    {
        return isset($properties['options']) ? $properties['options']:array();
    }

    /**
     * parseSchema
     *
     * A method to parse a Schema and translate it into a property array.
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the schema
     * @return array  $build    Built array of schema information
     */
    public function parseSchema($schema, $type)
    {
        $array = Doctrine_Parser::load($schema, $type);
        
        $build = array();
        
        foreach ($array as $className => $table) {
            $columns = array();
            
            $className = isset($table['className']) ? (string) $table['className']:(string) $className;
            
            if (isset($table['tableName']) && $table['tableName']) {
                $tableName = $table['tableName'];
            } else {
                if (isset($table['inheritance']['extends']) && isset($table['inheritance']['extends']['keyType']) && isset($table['inheritance']['extends']['keyValue'])) {
                    $tableName = null;
                } else {
                    $tableName = Doctrine::tableize($className);
                }
            }
            
            $columns = isset($table['columns']) ? $table['columns']:array();
            $columns = isset($table['fields']) ? $table['fields']:$columns;
            
            if ( ! empty($columns)) {
                foreach ($columns as $columnName => $field) {
                    $colDesc = array();
                    $colDesc['name'] = $columnName;
                    
                    $e = explode('(', $field['type']);
                    if (isset($e[0]) && isset($e[1])) {
                        $colDesc['type'] = $e[0];
                        $colDesc['length'] = substr($e[1], 0, strlen($e[1]) - 1);
                    } else {
                        $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                        $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                        $colDesc['length'] = isset($field['size']) ? (int) $field['size']:$colDesc['length'];
                    }
                    
                    $colDesc['ptype'] = isset($field['ptype']) ? (string) $field['ptype']:(string) $colDesc['type'];
                    $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                    $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                    $colDesc['default'] = isset($field['default']) ? $field['default']:null;
                    $colDesc['autoincrement'] = isset($field['autoincrement']) ? (bool) (isset($field['autoincrement']) && $field['autoincrement']):null;
                    $colDesc['autoincrement'] = isset($field['autoinc']) ? (bool) (isset($field['autoinc']) && $field['autoinc']):$colDesc['autoincrement'];
                    $colDesc['values'] = isset($field['values']) ? (array) $field['values']:null;
                    
                    $validators = Doctrine::getValidators();
                    
                    foreach ($validators as $validator) {
                        if (isset($field[$validator])) {
                            $colDesc[$validator] = $field[$validator];
                        }
                    }
                    
                    $columns[(string) $colDesc['name']] = $colDesc;
                }
            }
            
            $build[$className]['connection'] = isset($table['connection']) ? $table['connection']:null;
            $build[$className]['className'] = $className;
            $build[$className]['tableName'] = $tableName;
            $build[$className]['columns'] = $columns;
            $build[$className]['relations'] = isset($table['relations']) ? $table['relations']:array();
            $build[$className]['indexes'] = isset($table['indexes']) ? $table['indexes']:array();
            $build[$className]['attributes'] = isset($table['attributes']) ? $table['attributes']:array();
            $build[$className]['templates'] = isset($table['templates']) ? $table['templates']:array();
            $build[$className]['actAs'] = isset($table['actAs']) ? $table['actAs']:array();
            $build[$className]['options'] = isset($table['options']) ? $table['options']:array();
            $build[$className]['package'] = isset($table['package']) ? $table['package']:null;
            
            if (isset($table['inheritance'])) {
                $build[$className]['inheritance'] = $table['inheritance'];
            }
        }
        
        return $build;
    }

    /**
     * buildRelationships
     *
     * Loop through an array of schema information and build all the necessary relationship information
     * Will attempt to auto complete relationships and simplify the amount of information required for defining a relationship
     *
     * @param  string $array 
     * @return void
     */
    protected function _buildRelationships(&$array)
    {
        foreach ($array as $name => $properties) {
            if ( ! isset($properties['relations'])) {
                continue;
            }
            
            $className = $properties['className'];
            $relations = $properties['relations'];
            
            foreach ($relations as $alias => $relation) {
                $class = isset($relation['class']) ? $relation['class']:$alias;
                
                // Attempt to guess the local and foreign
                if (isset($relation['refClass'])) {
                    $relation['local'] = isset($relation['local']) ? $relation['local']:Doctrine::tableize($name) . '_id';
                    $relation['foreign'] = isset($relation['foreign']) ? $relation['foreign']:Doctrine::tableize($class) . '_id';
                } else {
                    $relation['local'] = isset($relation['local']) ? $relation['local']:Doctrine::tableize($class) . '_id';
                    $relation['foreign'] = isset($relation['foreign']) ? $relation['foreign']:'id';
                }
            
                $relation['alias'] = isset($relation['alias']) ? $relation['alias'] : $alias;
                $relation['class'] = $class;
                
                if (isset($relation['refClass'])) {
                    $relation['type'] = 'many';
                }
                
                if (isset($relation['type']) && $relation['type']) {
                    $relation['type'] = $relation['type'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                } else {
                    $relation['type'] = Doctrine_Relation::ONE;
                }

                if (isset($relation['foreignType']) && $relation['foreignType']) {
                    $relation['foreignType'] = $relation['foreignType'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                }
                
                $this->_relations[$className][$alias] = $relation;
            }
        }
        
        // Now we fix all the relationships and auto-complete opposite ends of relationships
        $this->_fixRelationships();
    }

    /**
     * fixRelationships
     *
     * Loop through all relationships building the opposite ends of each relationship
     *
     * @return void
     */
    protected function _fixRelationships()
    {
        foreach($this->_relations as $className => $relations) {
            foreach ($relations AS $alias => $relation) {
                $newRelation = array();
                $newRelation['foreign'] = $relation['local'];
                $newRelation['local'] = $relation['foreign'];
                $newRelation['class'] = isset($relation['foreignClass']) ? $relation['foreignClass']:$className;
                $newRelation['alias'] = isset($relation['foreignAlias']) ? $relation['foreignAlias']:$className;
                
                // this is so that we know that this relation was autogenerated and
                // that we do not need to include it if it is explicitly declared in the schema by the users.
                $newRelation['autogenerated'] = true; 
                
                if (isset($relation['refClass'])) {
                    $newRelation['refClass'] = $relation['refClass'];
                    $newRelation['type'] = isset($relation['foreignType']) ? $relation['foreignType']:$relation['type'];
                } else {                
                    if(isset($relation['foreignType'])) {
                        $newRelation['type'] = $relation['foreignType'];
                    } else {
                        $newRelation['type'] = $relation['type'] === Doctrine_Relation::ONE ? Doctrine_Relation::MANY:Doctrine_Relation::ONE;
                    }
                }
                
                if (!isset($this->_relations[$relation['class']][$newRelation['alias']])) {
                    $this->_relations[$relation['class']][$newRelation['alias']] = $newRelation;
                }
            }
        }
    }
}