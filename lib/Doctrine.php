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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @todo Remove all the constants, attributes to the new attribute system,
 *       All methods to appropriate classes.
 *       Finally remove this class.
 */
final class Doctrine
{
    /**
     * PDO derived constants
     */
    const CASE_NATURAL              = 0;
    const CASE_UPPER                = 1;
    const CASE_LOWER                = 2;
    const CURSOR_FWDONLY            = 0;
    const CURSOR_SCROLL             = 1;
    const ERRMODE_EXCEPTION         = 2;
    const ERRMODE_SILENT            = 0;
    const ERRMODE_WARNING           = 1;
    const FETCH_ASSOC               = 2;
    const FETCH_BOTH                = 4;
    const FETCH_BOUND               = 6;
    const FETCH_CLASS               = 8;
    const FETCH_CLASSTYPE           = 262144;
    const FETCH_COLUMN              = 7;
    const FETCH_FUNC                = 10;
    const FETCH_GROUP               = 65536;
    const FETCH_INTO                = 9;
    const FETCH_LAZY                = 1;
    const FETCH_NAMED               = 11;
    const FETCH_NUM                 = 3;
    const FETCH_OBJ                 = 5;
    const FETCH_ORI_ABS             = 4;
    const FETCH_ORI_FIRST           = 2;
    const FETCH_ORI_LAST            = 3;
    const FETCH_ORI_NEXT            = 0;
    const FETCH_ORI_PRIOR           = 1;
    const FETCH_ORI_REL             = 5;
    const FETCH_SERIALIZE           = 524288;
    const FETCH_UNIQUE              = 196608;
    const NULL_EMPTY_STRING         = 1;
    const NULL_NATURAL              = 0;
    const NULL_TO_STRING            = NULL;
    const PARAM_BOOL                = 5;
    const PARAM_INPUT_OUTPUT        = -2147483648;
    const PARAM_INT                 = 1;
    const PARAM_LOB                 = 3;
    const PARAM_NULL                = 0;
    const PARAM_STMT                = 4;
    const PARAM_STR                 = 2;
    const ATTR_AUTOCOMMIT           = 0;
    const ATTR_PREFETCH             = 1;
    const ATTR_TIMEOUT              = 2;
    const ATTR_ERRMODE              = 3;
    const ATTR_SERVER_VERSION       = 4;
    const ATTR_CLIENT_VERSION       = 5;
    const ATTR_SERVER_INFO          = 6;
    const ATTR_CONNECTION_STATUS    = 7;
    const ATTR_CASE                 = 8;
    const ATTR_CURSOR_NAME          = 9;
    const ATTR_CURSOR               = 10;
    const ATTR_ORACLE_NULLS         = 11;
    const ATTR_PERSISTENT           = 12;
    const ATTR_STATEMENT_CLASS      = 13;
    const ATTR_FETCH_TABLE_NAMES    = 14;
    const ATTR_FETCH_CATALOG_NAMES  = 15;
    const ATTR_DRIVER_NAME          = 16;
    const ATTR_STRINGIFY_FETCHES    = 17;
    const ATTR_MAX_COLUMN_LEN       = 18;

    /**
     * MODEL_LOADING_AGGRESSIVE
     *
     * Constant for agressive model loading
     * Will require_once() all found model files
     *
     * @see self::ATTR_MODEL_LOADING
     */
    const MODEL_LOADING_AGGRESSIVE   = 1;

    /**
     * MODEL_LOADING_CONSERVATIVE
     *
     * Constant for conservative model loading
     * Will not require_once() found model files inititally instead it will build an array
     * and reference it in autoload() when a class is needed it will require_once() it
     *
     * @see self::ATTR_MODEL_LOADING
     */
    const MODEL_LOADING_CONSERVATIVE = 2;

    /**
     * Path
     *
     * @var string $path            doctrine root directory
     */
    private static $_path;

    /**
     * _loadedModelFiles
     *
     * Array of all the loaded models and the path to each one for autoloading
     *
     * @var array
     */
    private static $_loadedModelFiles = array();
    private static $_pathModels = array();

    /**
     * __construct
     *
     * @return void
     * @throws Doctrine_Exception
     */
    public function __construct()
    {
        throw new Doctrine_Exception('Doctrine is static class. No instances can be created.');
    }

    public static function getLoadedModelFiles()
    {
        return self::$_loadedModelFiles;
    }
    
    public static function getPathModels()
    {
        return self::$_pathModels;
    }

    /**
     * getPath
     * returns the doctrine root
     *
     * @return string
     */
    public static function getPath()
    {
        if ( ! self::$_path) {
            self::$_path = dirname(__FILE__);
        }

        return self::$_path;
    }

    /**
     * loadModels
     *
     * Recursively load all models from a directory or array of directories
     *
     * @param string $directory    Path to directory of models or array of directory paths
     * @return array $loadedModels
     */
    public static function loadModels($directory)
    {
        $loadedModels = array();
        
        if ($directory !== null) {
            $manager = Doctrine_Manager::getInstance();
            
            foreach ((array) $directory as $dir) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),
                                                        RecursiveIteratorIterator::LEAVES_ONLY);
                foreach ($it as $file) {
                    $e = explode('.', $file->getFileName());
                    if (end($e) === 'php' && strpos($file->getFileName(), '.inc') === false) {
                        
                        if ($manager->getAttribute(Doctrine::ATTR_MODEL_LOADING) === Doctrine::MODEL_LOADING_CONSERVATIVE) {
                            self::$_loadedModelFiles[$e[0]] = $file->getPathName();
                            self::$_pathModels[$file->getPathName()][$e[0]] = $e[0];

                            $loadedModels[] = $e[0];
                        } else {
                            $declaredBefore = get_declared_classes();
                            require_once($file->getPathName());
                            
                            $declaredAfter = get_declared_classes();
                            // Using array_slice because array_diff is broken is some PHP versions
                            $foundClasses = array_slice($declaredAfter, count($declaredBefore) - 1);
                            if ($foundClasses) {
                                foreach ($foundClasses as $className) {
                                    if (self::isValidModelClass($className) && !in_array($className, $loadedModels)) {
                                        $loadedModels[] = $className;

                                        self::$_pathModels[$file->getPathName()][$className] = $className;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // We do not want to filter invalid models when using conservative model loading
        // The filtering requires that the class be loaded and inflected in order to determine if it is 
        // a valid class.
        if ($manager->getAttribute(Doctrine::ATTR_MODEL_LOADING) == Doctrine::MODEL_LOADING_CONSERVATIVE) {
            return $loadedModels;
        } else {
            return self::filterInvalidModels($loadedModels);
        }
    }

    /**
     * getLoadedModels
     *
     * Get all the loaded models, you can provide an array of classes or it will use get_declared_classes()
     *
     * Will filter through an array of classes and return the Doctrine_Entitys out of them.
     * If you do not specify $classes it will return all of the currently loaded Doctrine_Entitys
     *
     * @return array   $loadedModels
     */
    public static function getLoadedModels()
    {
        $classes = get_declared_classes();
        $classes = array_merge($classes, array_keys(self::$_loadedModelFiles));

        return self::filterInvalidModels($classes);
    }

    /**
     * filterInvalidModels
     *
     * Filter through an array of classes and return all the classes that are valid models
     * This will inflect the class, causing it to be loaded in to memory.
     *
     * @param classes  Array of classes to filter through, otherwise uses get_declared_classes()
     * @return array   $loadedModels
     */
    public static function filterInvalidModels($classes)
    {
        $validModels = array();

        foreach ((array) $classes as $name) {
            if (self::isValidModelClass($name) && !in_array($name, $validModels)) {
                $validModels[] = $name;
            }
        }

        return $validModels;
    }

    /**
     * isValidModelClass
     *
     * Checks if what is passed is a valid Doctrine_ORM_Entity
     * Will load class in to memory in order to inflect it and find out information about the class
     *
     * @param   mixed   $class Can be a string named after the class, an instance of the class, or an instance of the class reflected
     * @return  boolean
     */
    public static function isValidModelClass($class)
    {
        if ($class instanceof Doctrine_ORM_Entity) {
            $class = get_class($class);
        }

        if (is_string($class) && class_exists($class)) {
            $class = new ReflectionClass($class);
        }

        if ($class instanceof ReflectionClass) {
            // Skip the following classes
            // - abstract classes
            // - not a subclass of Doctrine_ORM_Entity
            // - don't have a setTableDefinition method
            if (!$class->isAbstract() &&
                $class->isSubClassOf('Doctrine_ORM_Entity')) {

                return true;
            }
        }

        return false;
    }

    /**
     * getConnectionByTableName
     *
     * Get the connection object for a table by the actual table name
     *
     * @param string $tableName
     * @return object Doctrine_Connection
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
     * method for importing existing schema to Doctrine_Entity classes
     *
     * @param string $directory Directory to write your models to
     * @param array $databases Array of databases to generate models for
     * @return boolean
     * @throws Exception
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
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tmp_doctrine_models';

        Doctrine::generateModelsFromDb($directory);

        $export = new Doctrine_Export_Schema();

        $result = $export->exportSchema($yamlPath, 'yml', $directory);

        Doctrine_Lib::removeDirectories($directory);

        return $result;
    }

    /**
     * generateModelsFromYaml
     *
     * Generate a yaml schema file from an existing directory of models
     *
     * @param string $yamlPath Path to your yaml schema files
     * @param string $directory Directory to generate your models in
     * @param array  $options Array of options to pass to the schema importer
     * @return void
     */
    public static function generateModelsFromYaml($yamlPath, $directory, $options = array())
    {
        $import = new Doctrine_Import_Schema();
        $import->setOptions($options);

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
     * createTablesFromArray
     *
     * Creates database tables for the models in the supplied array
     *
     * @param array $array An array of models to be exported
     * @return void
     */
    public static function createTablesFromArray($array)
    {
        return Doctrine_Manager::connection()->export->exportClasses($array);
    }

    /**
     * generateSqlFromModels
     *
     * @param string $directory
     * @return string $build  String of sql queries. One query per line
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
    public static function createDatabases($specifiedConnections = array())
    {
        return Doctrine_Manager::getInstance()->createDatabases($specifiedConnections);
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
        return Doctrine_Manager::getInstance()->dropDatabases($specifiedConnections);
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
        $data = new Doctrine_Data();

        if ( ! $append) {
            $data->purge();
        }

        return $data->importData($yamlPath, 'yml');
    }

    /**
     * migrate
     *
     * Migrate database to specified $to version. Migrates from current to latest if you do not specify.
     *
     * @param string $migrationsPath Path to migrations directory which contains your migration classes
     * @param string $to Version you wish to migrate to.
     * @return bool true
     * @throws new Doctrine_Migration_Exception
     */
    public static function migrate($migrationsPath, $to = null)
    {
        $migration = new Doctrine_Migration($migrationsPath);

        return $migration->migrate($to);
    }

    /**
     * generateMigrationClass
     *
     * Generate new migration class skeleton
     *
     * @param string $className Name of the Migration class to generate
     * @param string $migrationsPath Path to directory which contains your migration classes
     */
    public static function generateMigrationClass($className, $migrationsPath)
    {
        $builder = new Doctrine_Builder_Migration($migrationsPath);

        return $builder->generateMigrationClass($className);
    }

    /**
     * generateMigrationsFromDb
     *
     * @param string $migrationsPath
     * @return void
     * @throws new Doctrine_Migration_Exception
     */
    public static function generateMigrationsFromDb($migrationsPath)
    {
        $builder = new Doctrine_Builder_Migration($migrationsPath);

        return $builder->generateMigrationsFromDb();
    }

    /**
     * generateMigrationsFromModels
     *
     * @param string $migrationsPath
     * @param string $modelsPath
     * @return void
     */
    public static function generateMigrationsFromModels($migrationsPath, $modelsPath = null)
    {
        $builder = new Doctrine_Builder_Migration($migrationsPath);

        return $builder->generateMigrationsFromModels($modelsPath);
    }

    /**
     * getTable
     *
     * @param string $tableName
     * @return void
     */
    public static function getTable($tableName)
    {
        return Doctrine_Manager::table($tableName);
    }

    /**
     * autoload
     *
     * simple autoload function
     * returns true if the class was loaded, otherwise false
     *
     * @param string $classname
     * @return boolean
     */
    public static function autoload($className)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return false;
        }

        if ( ! self::$_path) {
            self::$_path = dirname(__FILE__);
        }

        $class = self::$_path . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        
        if (file_exists($class)) {
            require $class;

            return true;
        }

        /* TODO: Move the following code out of here. A generic Doctrine_Autoloader
           class that can be configured in various ways might be a good idea.
           Same goes for locate().*/
        $loadedModels = self::$_loadedModelFiles;

        if (isset($loadedModels[$className]) && file_exists($loadedModels[$className])) {
            require_once $loadedModels[$className];

            return true;
        }

        return false;
    }
    
    public static function locate($name)
    {
        $findPattern = self::$_path . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, str_replace('Doctrine_', '', $name));

        $matches = glob($findPattern);

        if ( isset($matches[0])) {
            return $matches[0];
        } else {
            return false;
        }
    }

    /**
     * tableize
     *
     * returns table name from class name
     *
     * @param string $classname
     * @return string
     */
    public static function tableize($className)
    {
         return Doctrine_TODO_Inflector::tableize($className);
    }

    /**
     * classify
     *
     * returns class name from table name
     *
     * @param string $tablename
     * @return string
     */
    public static function classify($tableName)
    {
        return Doctrine_TODO_Inflector::classify($tableName);
    }
}