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
 * class Doctrine_Export_Schema
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
 */
abstract class Doctrine_Export_Schema
{
    /**
     * build
     * 
     * Build the schema string to be dumped to file
     *
     * @param string $array 
     * @return void
     */
    abstract function build($array);
    
    /**
     * dump
     * 
     * Dump the array to the schema file
     *
     * @param string $array
     * @param string $schema
     * @return void
     */
    public function dump($array, $schema)
    {
        $data = $this->build($array);
        
        file_put_contents($schema, $data);
    }
    
    public function getDirectoryTables($directory)
    {
        $parent = new ReflectionClass('Doctrine_Record');
        
        $declared = get_declared_classes();
        
        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                    RecursiveIteratorIterator::LEAVES_ONLY);
                
                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    
                    if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                        require_once $file->getPathName();
                    }
                }
            }
            
            $declared = get_declared_classes();
            
            $tables = array();
            foreach($declared as $name)
            {
                $class = new ReflectionClass($name);
                
                if ($class->isSubclassOf($parent) AND !$class->isAbstract()) {
                    $tables[$name] = $name;
                }
                
            }
            
            return $tables;
        }
    }
    
    /**
     * buildSchema
     * 
     * Build schema array that can be dumped to file
     *
     * @param string $directory 
     * @return void
     */
    public function buildSchema($directory)
    {
        $array = array('tables' => array());
        
        $tables = $this->getDirectoryTables($directory);
        
        $parent = new ReflectionClass('Doctrine_Record');

        $sql = array();
        $fks = array();

        // we iterate trhough the diff of previously declared classes
        // and currently declared classes
        foreach ($tables as $name) {
            $class = new ReflectionClass($name);
            
            // check if class is an instance of Doctrine_Record and not abstract
            // class must have method setTableDefinition (to avoid non-Record subclasses like symfony's sfDoctrineRecord)
            // we have to recursively iterate through the class parents just to be sure that the classes using for example
            // column aggregation inheritance are properly exported to database
            while ($class->isAbstract() ||
                   ! $class->isSubclassOf($parent) ||
                   ! $class->hasMethod('setTableDefinition') ||
                   ( $class->hasMethod('setTableDefinition') &&
                     $class->getMethod('setTableDefinition')->getDeclaringClass()->getName() !== $class->getName())) {

                $class = $class->getParentClass();
                if ($class === false) {
                    break;
                }
            }

            if ($class === false) {
                continue;
            }

            $record = new $name();
            $table  = $record->getTable();
            
            $data = $table->getExportableFormat();
            
            $table = array();
            $table['name'] = $data['tableName'];
            $table['class'] = get_class($record);
            
            foreach ($data['columns'] AS $name => $column)
            {
                $data['columns'][$name]['name'] = $name;
            }
            
            $table['columns'] = $data['columns'];
            
            $array['tables'][$data['tableName']] = $table;
        }
        
        return $array;
    }
    
    /**
     * exportSchema
     *
     * @param string $schema 
     * @param string $directory 
     * @return void
     */
    public function exportSchema($schema, $directory)
    {
        $array = $this->buildSchema($directory);
        
        return $this->dump($array, $schema);
    }
}