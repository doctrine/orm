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
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1838 $
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class Doctrine_Import_Schema
{
    public $relations = array();
    
    /**
     * Parse the schema and return it in an array
     *
     * @param  string $schema
     * @access public
     */
    abstract function parseSchema($schema);
    
    /**
     * parse
     *
     * Function to do the actual parsing of the file
     *
     * @param string $schema 
     * @return void
     * @author Jonathan H. Wage
     */
     public function parse($schema)
     {
         $class = get_class($this);
         $type = strtolower(str_replace('Doctrine_Import_Schema_', '', $class));
         
         return Doctrine_Parser::load($schema, $type);
     }
     
    /**
     * importSchema
     *
     * A method to import a Schema and translate it into a Doctrine_Record object
     *
     * @param  string $schema       The file containing the XML schema
     * @param  string $directory    The directory where the Doctrine_Record class will
     *                              be written
     * @access public
     */
    public function importSchema($schema, $directory)
    {
        $builder = new Doctrine_Import_Builder();
        $builder->setTargetPath($directory);
        
        $array = array();
        foreach ((array) $schema AS $s) {
            $array = array_merge($array, $this->parseSchema($s));
        }
        
        $this->buildRelationships($array);
        
        foreach ($array as $name => $properties) {
            $options = array();
            $options['className'] = $properties['className'];
            $options['fileName'] = $directory.DIRECTORY_SEPARATOR.$properties['className'].'.class.php';
            
            $columns = $properties['columns'];
            
            $relations = isset($this->relations[$options['className']]) ? $this->relations[$options['className']]:array();
            
            $builder->buildRecord($options, $columns, $relations);
        }
    }  
    
    public function buildRelationships($array)
    {
        foreach ($array as $name => $properties) {
            $className = $properties['className'];     
            $relations = $properties['relations'];
            $columns = $properties['columns'];
            
            foreach ($relations as $alias => $relation) {
                $class = isset($relation['class']) ? $relation['class']:$alias;
                
                $relation['alias'] = $alias;
                $relation['class'] = $class;
                
                if (isset($relation['type']) && $relation['type']) {
                    $relation['type'] = $relation['type'] === 'one' ? Doctrine_Relation::ONE:Doctrine_Relation::MANY;
                } else {
                    $relation['type'] = Doctrine_Relation::ONE;
                }
                
                $this->relations[$className][$class] = $relation;
            }
        }
        
        /*
        // Fix the other end of the relations
        foreach($this->relations as $className => $relations) {
            foreach ($relations AS $alias => $relation) {
                $newRelation = array();
                $newRelation['foreign'] = $relation['local'];
                $newRelation['local'] = $relation['foreign'];
                $newRelation['class'] = $className;
                $newRelation['alias'] = $className;
                $newRelation['type'] = $relation['type'] === Doctrine_Relation::ONE ? Doctrine_Relation::MANY:Doctrine_Relation::ONE;
                
                $this->relations[$relation['class']][$className] = $newRelation;
            }
        }
        */
    }
}