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
 * <http://www.phpdoctrine.org>.
 */

#namespace Doctrine::Migration;

/**
 * Doctrine_Migration
 *
 * @package     Doctrine
 * @subpackage  Migration
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @todo Move to "Doctrine Migration" package. Separate download.
 */
class Doctrine_Migration
{
    protected $_changes = array('created_tables'      =>  array(),
                                'renamed_tables'      =>  array(),
                                'created_constraints' =>  array(),
                                'dropped_fks'         =>  array(),
                                'created_fks'         =>  array(),
                                'dropped_constraints' =>  array(),
                                'removed_indexes'     =>  array(),
                                'dropped_tables'      =>  array(),
                                'added_columns'       =>  array(),
                                'renamed_columns'     =>  array(),
                                'changed_columns'     =>  array(),
                                'removed_columns'     =>  array(),
                                'added_indexes'       =>  array()),
              $_migrationTableName = 'migration_version',
              $_migrationClassesDirectory = array(),
              $_migrationClasses = array(),
              $_loadedMigrations = array();

    /**
     * construct
     *
     * Specify the path to the directory with the migration classes.
     * The classes will be loaded and the migration table will be created if it does not already exist
     *
     * @param string $directory 
     * @return void
     */
    public function __construct($directory = null)
    {
        if ($directory != null) {
            $this->_migrationClassesDirectory = $directory;

            $this->_loadMigrationClasses();

            $this->_createMigrationTable();
        }
    }

    /**
     * getTableName
     *
     * @return void
     */
    public function getTableName()
    {
        return $this->_migrationTableName;
    }

    /**
     * setTableName
     *
     * @param string $tableName 
     * @return void
     */
    public function setTableName($tableName)
    {
        $this->_migrationTableName = Doctrine_Manager::connection()
                ->formatter->getTableName($tableName);
    }

    /**
     * createMigrationTable
     * 
     * Creates the migration table used to store the current version
     *
     * @return void
     */
    protected function _createMigrationTable()
    {
        $conn = Doctrine_Manager::connection();

        try {
            $conn->export->createTable($this->_migrationTableName, array('version' => array('type' => 'integer', 'size' => 11)));

            return true;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * loadMigrationClassesFromDirectory 
     * 
     * refactored out from loadMigrationClasses
     * $param array An array of classes
     * @return void
     */
    public function loadMigrationClassesFromDirectory($classes){
        foreach ((array) $this->_migrationClassesDirectory as $dir) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($it as $file) {
                $e = explode('.', $file->getFileName());
                if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                    if ( ! in_array($file->getFileName(), $this->_loadedMigrations)) {
                        require_once($file->getPathName());

                        $requiredClass = array_diff(get_declared_classes(), $classes);
                        $requiredClass = end($requiredClass);

                        if ($requiredClass) {
                            $this->_loadedMigrations[$requiredClass] = $file->getFileName();
                        }
                    }
                }
            }
        }
    }

    /**
     * loadMigrationClasses
     *
     * Loads the migration classes for the directory specified by the constructor
     *
     * @return void
     */
    protected function _loadMigrationClasses()
    {
        if ($this->_migrationClasses) {
            return $this->_migrationClasses;
        }

        $classes = get_declared_classes();

        if ($this->_migrationClassesDirectory !== null) {
            $this->loadMigrationClassesFromDirectory($classes);
        }

        $parent = new ReflectionClass('Doctrine_Migration');

        foreach ($this->_loadedMigrations as $name => $fileName) {
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

            $e = explode('_', $fileName);
            $classMigrationNum = (int) $e[0];

            $this->_migrationClasses[$classMigrationNum] = array('className' => $name, 'fileName' => $fileName);
        }

        return $this->_migrationClasses;
    }

    /**
     * getMigrationClasses
     *
     * @return void
     */
    public function getMigrationClasses()
    {
        return $this->_migrationClasses;
    }

    /**
     * setCurrentVersion
     *
     * Sets the current version in the migration table
     *
     * @param string $number 
     * @return void
     */
    protected function _setCurrentVersion($number)
    {
        $conn = Doctrine_Manager::connection();

        if ($this->hasMigrated()) {
            $conn->exec("UPDATE " . $this->_migrationTableName . " SET version = $number");
        } else {
            $conn->exec("INSERT INTO " . $this->_migrationTableName . " (version) VALUES ($number)");
        }
    }

    /**
     * getCurrentVersion
     *
     * Get the current version of the database
     *
     * @return void
     */
    public function getCurrentVersion()
    {
        $conn = Doctrine_Manager::connection();

        $result = $conn->fetchColumn("SELECT version FROM " . $this->_migrationTableName);

        return isset($result[0]) ? $result[0]:0;
    }

    /**
     * hasMigrated
     *
     * Returns true/false for whether or not this database has been migrated in the past
     *
     * @return void
     */
    public function hasMigrated()
    {
        $conn = Doctrine_Manager::connection();

        $result = $conn->fetchColumn("SELECT version FROM " . $this->_migrationTableName);

        return isset($result[0]) ? true:false; 
    }

    /**
     * getLatestVersion
     *
     * Gets the latest possible version from the loaded migration classes
     *
     * @return void
     */
    public function getLatestVersion()
    {
        $this->_loadMigrationClasses();

        $versions = array();
        foreach (array_keys($this->_migrationClasses) as $classMigrationNum) {
            $versions[$classMigrationNum] = $classMigrationNum;
        }

        rsort($versions);

        return isset($versions[0]) ? $versions[0]:0;
    }

    /**
     * getNextVersion
     *
     * @return integer $nextVersion
     */
    public function getNextVersion()
    {
        return $this->getLatestVersion() + 1;
    }

    /**
     * getMigrationClass
     *
     * Get instance of migration class for $num
     *
     * @param string $num 
     * @return void
     */
    protected function _getMigrationClass($num)
    {
        foreach ($this->_migrationClasses as $classMigrationNum => $info) {
            $className = $info['className'];

            if ($classMigrationNum == $num) {
                return new $className();
            }
        }

        throw new Doctrine_Migration_Exception('Could not find migration class for migration step: '.$num);
    }

    /**
     * doMigrateStep
     *
     * Perform migration directory for the specified version. Loads migration classes and performs the migration then processes the changes
     *
     * @param string $direction 
     * @param string $num 
     * @return void
     */
    protected function _doMigrateStep($direction, $num)
    {
        $migrate = $this->_getMigrationClass($num);

        $migrate->_doMigrate($direction);
    }

    /**
     * doMigrate
     * 
     * Perform migration for a migration class. Executes the up or down method then processes the changes
     *
     * @param string $direction 
     * @return void
     */
    protected function _doMigrate($direction)
    {
        $method = 'pre'.$direction;
        $this->$method();

        if ( method_exists($this, $direction)) {
            $this->$direction();
    
            foreach ($this->_changes as $type => $changes) {
                if ( ! empty($changes)) {
                    $funcName = 'process' . Doctrine::classify($type);
    
                    $process = new Doctrine_Migration_Process();
                    $process->$funcName($changes); 
                }
            }
        }

        $method = 'post'.$direction;
        $this->$method();
    }

    /**
     * migrate
     *
     * Perform a migration chain by specifying the $from and $to.
     * If you do not specify a $from or $to then it will attempt to migrate from the current version to the latest version
     *
     * @param string $from 
     * @param string $to 
     * @return void
     */
    public function migrate($to = null)
    {
        $from = $this->getCurrentVersion();

        // If nothing specified then lets assume we are migrating from the current version to the latest version
        if ($to === null) {
            $to = $this->getLatestVersion();
        }

        if ($from == $to) {
            throw new Doctrine_Migration_Exception('Already at version # ' . $to);
        }

        $direction = $from > $to ? 'down':'up';

        if ($direction === 'up') {
            for ($i = $from + 1; $i <= $to; $i++) {
                $this->_doMigrateStep($direction, $i);
            }
        } else {
            for ($i = $from; $i > $to; $i--) {
                $this->_doMigrateStep($direction, $i);
            }
        }

        $this->_setCurrentVersion($to);

        return $to;
    }

    /**
     * addChange
     *
     * @param string $type 
     * @param string $array 
     * @return void
     */
    protected function _addChange($type, array $change = array())
    {
        $this->_changes[$type][] = $change;
    }

    /**
     * createTable
     *
     * @param string $tableName 
     * @param string $array 
     * @param string $array 
     * @return void
     */
    public function createTable($tableName, array $fields = array(), array $options = array())
    {
        $options = get_defined_vars();

        $this->_addChange('created_tables', $options);
    }

    /**
     * dropTable
     *
     * @param string $tableName 
     * @return void
     */
    public function dropTable($tableName)
    {
        $options = get_defined_vars();

        $this->_addChange('dropped_tables', $options);
    }

    /**
     * renameTable
     *
     * @param string $oldTableName 
     * @param string $newTableName 
     * @return void
     */
    public function renameTable($oldTableName, $newTableName)
    {
        $options = get_defined_vars();

        $this->_addChange('renamed_tables', $options);
    }

    /**
     * createConstraint
     *
     * @param string $tableName
     * @param string $constraintName
     * @return void
     */
    public function createConstraint($tableName, $constraintName, array $definition)
    {
        $options = get_defined_vars();

        $this->_addChange('created_constraints', $options);
    }

    /**
     * dropConstraint
     *
     * @param string $tableName
     * @param string $constraintName
     * @return void
     */
    public function dropConstraint($tableName, $constraintName, $primary = false)
    {
        $options = get_defined_vars();

        $this->_addChange('dropped_constraints', $options);
    }

    /**
     * createForeignKey
     *
     * @param string $tableName
     * @param string $constraintName
     * @return void
     */
    public function createForeignKey($tableName, array $definition)
    {
        $options = get_defined_vars();

        $this->_addChange('created_fks', $options);
    }

    /**
     * dropForeignKey
     *
     * @param string $tableName
     * @param string $constraintName
     * @return void
     */
    public function dropForeignKey($tableName, $fkName)
    {
        $options = get_defined_vars();

        $this->_addChange('dropped_fks', $options);
    }

    /**
     * addColumn
     *
     * @param string $tableName 
     * @param string $columnName 
     * @param string $type 
     * @param string $array 
     * @return void
     */
    public function addColumn($tableName, $columnName, $type, array $options = array())
    {
        $options = get_defined_vars();

        $this->_addChange('added_columns', $options);
    }

    /**
     * renameColumn
     *
     * @param string $tableName 
     * @param string $oldColumnName 
     * @param string $newColumnName 
     * @return void
     */
    public function renameColumn($tableName, $oldColumnName, $newColumnName)
    {
        $options = get_defined_vars();

        $this->_addChange('renamed_columns', $options);
    }

    /**
     * renameColumn
     *
     * @param string $tableName 
     * @param string $columnName 
     * @param string $type 
     * @param string $array 
     * @return void
     */
    public function changeColumn($tableName, $columnName, $type, array $options = array())
    {
        $options = get_defined_vars();

        $this->_addChange('changed_columns', $options);
    }

    /**
     * removeColumn
     *
     * @param string $tableName 
     * @param string $columnName 
     * @return void
     */
    public function removeColumn($tableName, $columnName)
    {
        $options = get_defined_vars();

        $this->_addChange('removed_columns', $options);
    }

    /**
     * addIndex
     *
     * @param string $tableName 
     * @param string $indexName 
     * @param string $array 
     * @return void
     */
    public function addIndex($tableName, $indexName, array $definition)
    {
        $options = get_defined_vars();

        $this->_addChange('added_indexes', $options);
    }

    /**
     * removeIndex
     *
     * @param string $tableName 
     * @param string $indexName 
     * @return void
     */
    public function removeIndex($tableName, $indexName)
    {
        $options = get_defined_vars();

        $this->_addChange('removed_indexes', $options);
    }

    /**
     * preUp
     *
     * @return void
     */
    public function preUp()
    {
        return;
    }

    /**
     * postUp
     *
     * @return void
     */
    public function postUp()
    {
        return;
    }

    /**
     * preDown
     *
     * @return void
     */
    public function preDown()
    {
        return;
    }

    /**
     * postDown
     *
     * @return void
     */
    public function postDown()
    {
        return;
    }

}