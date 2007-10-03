<?php
/*
 *  $Id: Migration.php 1080 2007-02-10 18:17:08Z jwage $
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
 * Doctrine_Migration
 *
 * this class represents a database view
 *
 * @package     Doctrine
 * @subpackage  Migration
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Migration
{
    public $changes = array('created_tables'    =>  array(),
                            'dropped_tables'    =>  array(),
                            'renamed_tables'    =>  array(),
                            'added_columns'     =>  array(),
                            'renamed_columns'   =>  array(),
                            'changed_columns'   =>  array(),
                            'removed_columns'   =>  array(),
                            'added_indexes'     =>  array(),
                            'removed_indexes'   =>  array());
    
    public $migrationTableName = 'migration_version';
    public $migrationClassesDirectory = array();
    public $migrationClasses = array();
    
    public function __construct($directory = null)
    {
        if ($directory != null) {
            $this->migrationClassesDirectory = $directory;
            
            $this->loadMigrationClasses();
        }
    }
    
    protected function loadMigrationClasses()
    {
        $directory = $this->migrationClassesDirectory;
        
        $classes = get_declared_classes();

        if ($directory !== null) {
            foreach ((array) $directory as $dir) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                        RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                        require_once $file->getPathName();
                        $requiredClass = array_diff(get_declared_classes(), $classes);
                        $requiredClass = end($requiredClass);
                        
                        $loadedClasses[$requiredClass] = $file->getFileName();
                        
                    }
                }
            }
        }
        
        $classes = $loadedClasses;
        $parent = new ReflectionClass('Doctrine_Migration');
        $loadedClasses = array();
        
        foreach ($classes as $name => $fileName) {
            $class = new ReflectionClass($name);
            
            while ($class->isSubclassOf($parent)) {

                $class = $class->getParentClass();
                if ($class === false) {
                    break;
                }
            }
            
            if ($class === false) {
                continue;
            }
            
            $loadedClasses[$name] = $fileName;
        }
        
        $this->migrationClasses = $loadedClasses;
        
        return $loadedClasses;
    }
    
    public function setCurrentVersion($number)
    {
        $conn = Doctrine_Manager::connection();
        
        try {
            $conn->export->createTable('migration_version', array('version' => array('type' => 'integer', 'size' => 11)));
        } catch(Exception $e) {
            
        }
        
        $current = self::getCurrentVersion();
        
        if (!$current) {
            $conn->exec("INSERT INTO migration_version (version) VALUES ($number)");
        } else {
            $conn->exec("UPDATE migration_version SET version = $number");
        }
    }
    
    public function getCurrentVersion()
    {
        $conn = Doctrine_Manager::connection();
        
        $result = $conn->fetchColumn("SELECT version FROM migration_version");
        
        return isset($result[0]) ? $result[0]:false;
    }
    
    public function migrate($from, $to)
    {
        if ($from === $to) {
            throw new Doctrine_Migration_Exception('You specified an invalid migration path. The from and to cannot be the same.');
        }
        
        $direction = $from > $to ? 'down':'up';
        
        if ($direction === 'up') {
            for ($i = $from + 1; $i <= $to; $i++) {
                $this->doMigrateStep($direction, $i);
            }
        } else {
            for ($i = $from; $i > $to; $i--) {
                $this->doMigrateStep($direction, $i);
            }
        }
        
        $this->setCurrentVersion($to);
    }
    
    protected function getMigrationClass($num)
    {
        $classes = $this->migrationClasses;
        
        foreach ($classes as $className => $fileName) {
            $e = explode('_', $fileName);
            $classMigrationNum = (int) $e[0];
            
            if ($classMigrationNum === $num) {
                return new $className();
            }
        }
        
        throw new Doctrine_Migration_Exception('Could not find migration class for migration step: '.$num);
    }
    
    public function doMigrateStep($direction, $num)
    {
        $migrate = $this->getMigrationClass($num);
        $migrate->doMigrate($direction);
    }
    
    public function doMigrate($direction)
    {
        if (method_exists($this, $direction)) {
            $this->$direction();

            $this->processChanges();
        }
    }
    
    public function processChanges()
    {
        foreach ($this->changes as $type => $changes) {
            $process = new Doctrine_Migration_Process();
            $funcName = 'process' . Doctrine::classify($type);
            $process->$funcName($changes); 
        }
    }
    
    public function addChange($type, array $change = array())
    {
        $this->changes[$type][] = $change;
    }
    
    public function createTable($tableName, array $fields = array(), array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('created_tables', $options);
    }
    
    public function dropTable($tableName)
    {
        $options = get_defined_vars();
        
        $this->addChange('dropped_tables', $options);
    }
    
    public function renameTable($oldTableName, $newTableName)
    {
        $options = get_defined_vars();
        
        $this->addChange('renamed_tables', $options);
    }
    
    public function addColumn($tableName, $columnName, $type, array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('added_columns', $options);
    }
    
    public function renameColumn($tableName, $oldColumnName, $newColumnName)
    {
        $options = get_defined_vars();
        
        $this->addChange('renamed_columns', $options);
    }
    
    public function changeColumn($tableName, $columnName, $type, array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('changed_columns', $options);
    }
    
    public function removeColumn($tableName, $columnName)
    {
        $options = get_defined_vars();
        
        $this->addChange('removed_columns', $options);
    }
    
    public function addIndex($tableName, $indexName, array $options = array())
    {
        $options = get_defined_vars();
        
        $this->addChange('added_indexes', $options);
    }
    
    public function removeIndex($tableName, $indexName)
    {
        $options = get_defined_vars();
        
        $this->addChange('removed_indexes', $options);
    }
}