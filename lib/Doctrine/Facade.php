<?php
/*
 *  $Id: Facade.php 2761 2007-10-07 23:42:29Z zYne $
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
 * Doctrine_Facade
 *
 * @package     Doctrine
 * @subpackage  Facade
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2761 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Facade
{
    /**
     * loadAllRuntimeClasses
     *
     * loads all runtime classes
     *
     * @return void
     */
    public static function loadAllRuntimeClasses()
    {
        $classes = Doctrine_Compiler::getRuntimeClasses();

        foreach ($classes as $class) {
            Doctrine::autoload($class);
        }
    }
    
    /**
     * loadModels
     *
     * Recursively load all models from a directory or array of directories
     * 
     * @param string $directory Path to directory of models or array of directory paths
     * @return void
     */
    public static function loadModels($directory)
    {
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
            
            $declared = array_diff(get_declared_classes(), $declared);
        }
        
        return self::getLoadedModels($declared);
    }
    
    /**
     * getLoadedModels
     *
     * Get all the loaded models, you can provide an array of classes or it will use get_declared_classes()
     * 
     * @param $classes Array of classes to filter through, otherwise uses get_declared_classes()
     * @return void
     */
    public static function getLoadedModels($classes = null)
    {
        if ($classes === null) {
            $classes = get_declared_classes();
        }
        
        $parent = new ReflectionClass('Doctrine_Record');
        
        $loadedModels = array();
        
        // we iterate trhough the diff of previously declared classes
        // and currently declared classes
        foreach ($classes as $name) {
            $class = new ReflectionClass($name);
            
            // Skip the following classes
            // - abstract classes
            // - not a subclass of Doctrine_Record 
            // - don't have a setTableDefinition method
            if ($class->isAbstract() || 
                !$class->isSubClassOf($parent) || 
                !$class->hasMethod('setTableDefinition')) {
                continue;
            }
            
            $loadedModels[] = $name;
        }
        
        return $loadedModels;
    }
    
    /**
     * getConnectionByTableName
     *
     * Get the connection object for a table by the actual table name
     * 
     * @param string $tableName 
     * @return void
     */
    public static function getConnectionByTableName($tableName)
    {
        $loadedModels = self::getLoadedModels();
        
        foreach ($loadedModels as $name) {
            $model = new $name();
            $table = $model->getTable();
            
            if ($table->getTableName() == $tableName) {
               return $table->getConnection(); 
            }
        }
        
        return Doctrine_Manager::connection();
    }
    
    /**
     * generateModelsFromDb
     *
     * method for importing existing schema to Doctrine_Record classes
     *
     * @param string $directory Directory to write your models to
     * @param array $databases Array of databases to generate models for
     * @return boolean
     */
    public static function generateModelsFromDb($directory, array $databases = array())
    {
        return Doctrine_Manager::connection()->import->importSchema($directory, $databases);
    }
    
    /**
     * generateYamlFromDb
     *
     * Generates models from database to temporary location then uses those models to generate a yaml schema file.
     * This should probably be fixed. We should write something to generate a yaml schema file directly from the database.
     *
     * @param string $yamlPath Path to write oyur yaml schema file to
     * @return void
     */
    public static function generateYamlFromDb($yamlPath)
    {
        $directory = '/tmp/tmp_doctrine_models';

        Doctrine::generateModelsFromDb($directory);

        $export = new Doctrine_Export_Schema();
        
        return $export->exportSchema($yamlPath, 'yml', $directory);
    }
    /**
     * generateModelsFromYaml
     *
     * Generate a yaml schema file from an existing directory of models
     *
     * @param string $yamlPath Path to your yaml schema files
     * @param string $directory Directory to generate your models in
     * @return void
     */
    public static function generateModelsFromYaml($yamlPath, $directory)
    {
        $import = new Doctrine_Import_Schema();
        $import->generateBaseClasses(true);
        
        return $import->importSchema($yamlPath, 'yml', $directory);
    }
    
    /**
     * createTablesFromModels
     *
     * Creates database tables for the models in the specified directory
     *
     * @param string $directory Directory containing your models
     * @return void
     */
    public static function createTablesFromModels($directory = null)
    {
        return Doctrine_Manager::connection()->export->exportSchema($directory);
    }
    
    /**
     * generateSqlFromModels
     *
     * @param string $directory 
     * @return void
     */
    public static function generateSqlFromModels($directory = null)
    {
        $sql = Doctrine_Manager::connection()->export->exportSql($directory);
        
        $build = '';
        foreach ($sql as $query) {
            $build .= $query.";\n";
        }
        
        return $build;
    }

    /**
     * generateYamlFromModels
     *
     * Generate yaml schema file for the models in the specified directory
     *
     * @param string $yamlPath Path to your yaml schema files
     * @param string $directory Directory to generate your models in
     * @return void
     */
    public static function generateYamlFromModels($yamlPath, $directory)
    {
        $export = new Doctrine_Export_Schema();
        
        return $export->exportSchema($yamlPath, 'yml', $directory);
    }
    
    /**
     * createDatabases
     *
     * Creates databases for connections
     *
     * @param string $specifiedConnections Array of connections you wish to create the database for
     * @return void
     */
    public static function createDatabases($specifiedConnections)
    {
        if (!is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }
        
        $connections = Doctrine_Manager::getInstance()->getConnections();
        
        foreach ($connections as $name => $connection) {
            if (!empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }
            
            $connection->export->createDatabase($name);
        }
    }
    
    /**
     * dropDatabases
     *
     * Drops databases for connections
     *
     * @param string $specifiedConnections Array of connections you wish to drop the database for
     * @return void
     */
    public static function dropDatabases($specifiedConnections = array())
    {
        if (!is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }
        
        $connections = Doctrine_Manager::getInstance()->getConnections();
        
        foreach ($connections as $name => $connection) {
            if (!empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }
            
            $connection->export->dropDatabase($name);
        }
    }
    
    /**
     * dumpData
     *
     * Dump data to a yaml fixtures file
     *
     * @param string $yamlPath Path to write the yaml data fixtures to
     * @param string $individualFiles Whether or not to dump data to individual fixtures files
     * @return void
     */
    public static function dumpData($yamlPath, $individualFiles = false)
    {
        $data = new Doctrine_Data();
        
        return $data->exportData($yamlPath, 'yml', array(), $individualFiles);
    }
    
    /**
     * loadData
     *
     * Load data from a yaml fixtures file.
     * The output of dumpData can be fed to loadData
     *
     * @param string $yamlPath Path to your yaml data fixtures
     * @param string $append Whether or not to append the data
     * @return void
     */
    public static function loadData($yamlPath, $append = false)
    {
        $delete = isset($append) ? ($append ? false : true) : true;

        if ($delete)
        {
          $models = Doctrine::getLoadedModels();

          foreach ($models as $model)
          {
            $model = new $model();

            $model->getTable()->createQuery()->delete($model)->execute();
          }
        }

        $data = new Doctrine_Data();
        
        return $data->importData($yamlPath, 'yml');
    }
    
    /**
     * loadDummyData
     *
     * Populdate your models with dummy data
     *
     * @param string $append Whether or not to append the data
     * @param string $num Number of records to populate
     * @return void
     */
    public static function loadDummyData($append, $num = 5)
    {
        $delete = isset($append) ? ($append ? false : true) : true;

        if ($delete)
        {
          $models = Doctrine::getLoadedModels();

          foreach ($models as $model)
          {
            $model = new $model();

            $model->getTable()->createQuery()->delete($model)->execute();
          }
        }
        
        $data = new Doctrine_Data();
        
        return $data->importDummyData($num);
    }
    
    /**
     * migrate
     * 
     * Migrate database to specified $to version. Migrates from current to latest if you do not specify.
     *
     * @param string $directory Directory which contains your migration classes
     * @param string $to Version you wish to migrate to.
     * @return void
     */
    public static function migrate($directory, $to = null)
    {
        $migration = new Doctrine_Migration($directory);
        
        return $migration->migrate($to);
    }
    
    /**
     * generateMigrationClass
     *
     * Generate new migration class skeleton
     *
     * @param string $className Name of the Migration class to generate
     * @param string $directory Directory which contains your migration classes
     * @package default
     */
    public static function generateMigrationClass($className, $directory)
    {
        $migration = new Doctrine_Migration($directory);
        $next = (string) $migration->getNextVersion();
        
        $fileName = str_repeat('0', (3 - strlen($next))) . $next . '_' . Doctrine::tableize($className) . '.class.php';
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;
        
        $code  = '<?php' . PHP_EOL;
        $code .= "// Automatically generated by the Doctrine ORM Framework\n";
        $code .= "class " . Doctrine::classify($className) . " extends Doctrine_Migration\n";
        $code .= "{\n";
        $code .= "\tpublic function up()\n\t{ }\n\n";
        $code .= "\tpublic function down()\n\t{ }\n";
        $code .= "}";
        
        file_put_contents($path, $code);
    }
    
    /**
     * compile
     *
     * @param string $target 
     * @return void
     */
    public static function compile($target = null)
    {
        return Doctrine_Compiler::compile($target);
    }
}