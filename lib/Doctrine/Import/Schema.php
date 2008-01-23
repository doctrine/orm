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
 * <http://www.phpdoctrine.org>.
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
     * getOptions
     *
     * @return void
     */
    public function getOptions()
    {
        return $this->_options;
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
            } else {
              $array = array_merge($array, $this->parseSchema($s, $format));
            }
        }

        $array = $this->_buildRelationships($array);

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
        $builder = new Doctrine_Builder_Record();
        $builder->setTargetPath($directory);
        $builder->setOptions($this->getOptions());
        
        $array = $this->buildSchema($schema, $format);

        foreach ($array as $name => $definition) {
            if ( ! empty($models) && !in_array($definition['className'], $models)) {
                continue;
            }
            
            $builder->buildRecord($definition);
        }
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
        $defaults = array('className'           =>  null,
                          'tableName'           =>  null,
                          'connection'          =>  null,
                          'relations'           =>  array(),
                          'indexes'             =>  array(),
                          'attributes'          =>  array(),
                          'templates'           =>  array(),
                          'actAs'               =>  array(),
                          'options'             =>  array(),
                          'package'             =>  null,
                          'inheritance'         =>  array(),
                          'subclasses'          =>  array(),
                          'detect_relations'    =>  false,
                          'generate_accessors'  =>  false);
        
        $array = Doctrine_Parser::load($schema, $type);

        // Go through the schema and look for global values so we can assign them to each table/class
        $globals = array();
        $globalKeys = array('connection',
                            'attributes',
                            'templates',
                            'actAs',
                            'options',
                            'package',
                            'inheritance',
                            'detect_relations',
                            'generate_accessors');

        // Loop over and build up all the global values and remove them from the array
        foreach ($array as $key => $value) {
            if (in_array($key, $globalKeys)) {
                unset($array[$key]);
                $globals[$key] = $value;
            }
        }

        // Apply the globals to each table if it does not have a custom value set already
        foreach ($array as $className => $table) {
            foreach ($globals as $key => $value) {
                if (!isset($array[$className][$key])) {
                    $array[$className][$key] = $value;
                }
            }
        }

        $build = array();

        foreach ($array as $className => $table) {
            $columns = array();

            $className = isset($table['className']) ? (string) $table['className']:(string) $className;

            if (isset($table['tableName']) && $table['tableName']) {
                $tableName = $table['tableName'];
            } else {
                if (isset($table['inheritance']['extends'])) {
                    $tableName = null;
                } else {
                    $tableName = Doctrine::tableize($className);
                }
            }

            $columns = isset($table['columns']) ? $table['columns']:array();

            if ( ! empty($columns)) {
                foreach ($columns as $columnName => $field) {
                    // Support short syntax: my_column: integer(4)
                    if (!is_array($field)) {
                        $original = $field;
                        $field = array();
                        $field['type'] = $original;
                    }

                    $colDesc = array();
                    $colDesc['name'] = $columnName;

                    // Support short type(length) syntax: my_column: { type: integer(4) }
                    $e = explode('(', $field['type']);
                    if (isset($e[0]) && isset($e[1])) {
                        $colDesc['type'] = $e[0];
                        $colDesc['length'] = substr($e[1], 0, strlen($e[1]) - 1);
                    } else {
                        $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                        $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                        $colDesc['length'] = isset($field['size']) ? (int) $field['size']:$colDesc['length'];
                    }

                    $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                    $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                    $colDesc['default'] = isset($field['default']) ? $field['default']:null;
                    $colDesc['autoincrement'] = isset($field['autoincrement']) ? (bool) (isset($field['autoincrement']) && $field['autoincrement']):null;
                    $colDesc['sequence'] = isset($field['sequence']) ? (string) $field['sequence']:null;
                    $colDesc['values'] = isset($field['values']) ? (array) $field['values']:null;

                    // Include all the specified and valid validators in the colDesc
                    $validators = Doctrine_Lib::getValidators();

                    foreach ($validators as $validator) {
                        if (isset($field[$validator])) {
                            $colDesc[$validator] = $field[$validator];
                        }
                    }

                    $columns[(string) $colDesc['name']] = $colDesc;
                }
            }

            // Apply the default values
            foreach ($defaults as $key => $defaultValue) {
                if (isset($table[$key]) && !isset($build[$className][$key])) {
                    $build[$className][$key] = $table[$key];
                } else {
                    $build[$className][$key] = isset($build[$className][$key]) ? $build[$className][$key]:$defaultValue;
                }
            }

            $build[$className]['className'] = $className;
            $build[$className]['tableName'] = $tableName;
            $build[$className]['columns'] = $columns;

            // Make sure that anything else that is specified in the schema makes it to the final array
            $build[$className] = Doctrine_Lib::arrayDeepMerge($table, $build[$className]);
            
            // We need to keep track of the className for the connection
            $build[$className]['connectionClassName'] = $build[$className]['className'];
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
    protected function _buildRelationships($array)
    {
        // Handle auto detecting relations by the names of columns
        // User.contact_id will automatically create User hasOne Contact local => contact_id, foreign => id
        foreach ($array as $className => $properties) {
            if (isset($properties['columns']) && !empty($properties['columns']) && isset($properties['detect_relations']) && $properties['detect_relations']) {
                foreach ($properties['columns'] as $column) {
                    $columnClassName = Doctrine_Inflector::classify(str_replace('_id', '', $column['name']));
                    if (isset($array[$columnClassName]) && !isset($array[$className]['relations'][$columnClassName])) {
                        $array[$className]['relations'][$columnClassName] = array();
                    }
                }
            }
        }

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
                
                $relation['key'] = $this->_buildUniqueRelationKey($relation);
                
                $this->_relations[$className][$alias] = $relation;
            }
        }
        
        // Now we auto-complete opposite ends of relationships
        $this->_autoCompleteOppositeRelations();
        
        // Make sure we do not have any duplicate relations
        $this->_fixDuplicateRelations();
        
        foreach ($this->_relations as $className => $relations) {
            $array[$className]['relations'] = $relations;
        }
        
        return $array;
    }

    /**
     * fixRelationships
     *
     * Loop through all relationships building the opposite ends of each relationship
     * and make sure no duplicate relations exist
     *
     * @return void
     */
    protected function _autoCompleteOppositeRelations()
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
                
                if ( ! isset($this->_relations[$relation['class']][$newRelation['alias']])) {
                    $newRelation['key'] = $this->_buildUniqueRelationKey($newRelation);
                    $this->_relations[$relation['class']][$newRelation['alias']] = $newRelation;
                }
            }
        }
    }
    
    protected function _fixDuplicateRelations()
    {
        foreach($this->_relations as $className => $relations) {
            // This is for checking for duplicates between alias-relations and a auto-generated relations to ensure the result set of unique relations
            $existingRelations = array();
            $uniqueRelations = array();
            foreach ($relations as $relation) {
                if ( ! in_array($relation['key'], $existingRelations)) {
                    $existingRelations[] = $relation['key'];
                    $uniqueRelations = array_merge($uniqueRelations, array($relation['alias'] => $relation));
                } else {
                    // check to see if this relationship is not autogenerated, if it's not, then the user must have explicitly declared it
                    if (!isset($relation['autogenerated']) || $relation['autogenerated'] != true) {
                        $uniqueRelations = array_merge($uniqueRelations, array($relation['alias'] => $relation));
                    }
                }
            }
            
            $this->_relations[$className] = $uniqueRelations;
        }
    }

    /**
     * _buildUniqueRelationKey
     *
     * @param string $relation 
     * @return void
     */
    protected function _buildUniqueRelationKey($relation)
    {
      return md5($relation['local'].$relation['foreign'].$relation['class'].(isset($relation['refClass']) ? $relation['refClass']:null));
    }
}