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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
final class Doctrine
{
    /**
     * VERSION
     */
    const VERSION                   = '1.0.0';

    /**
     * ERROR CONSTANTS
     */
    const ERR                       = -1;
    const ERR_SYNTAX                = -2;
    const ERR_CONSTRAINT            = -3;
    const ERR_NOT_FOUND             = -4;
    const ERR_ALREADY_EXISTS        = -5;
    const ERR_UNSUPPORTED           = -6;
    const ERR_MISMATCH              = -7;
    const ERR_INVALID               = -8;
    const ERR_NOT_CAPABLE           = -9;
    const ERR_TRUNCATED             = -10;
    const ERR_INVALID_NUMBER        = -11;
    const ERR_INVALID_DATE          = -12;
    const ERR_DIVZERO               = -13;
    const ERR_NODBSELECTED          = -14;
    const ERR_CANNOT_CREATE         = -15;
    const ERR_CANNOT_DELETE         = -16;
    const ERR_CANNOT_DROP           = -17;
    const ERR_NOSUCHTABLE           = -18;
    const ERR_NOSUCHFIELD           = -19;
    const ERR_NEED_MORE_DATA        = -20;
    const ERR_NOT_LOCKED            = -21;
    const ERR_VALUE_COUNT_ON_ROW    = -22;
    const ERR_INVALID_DSN           = -23;
    const ERR_CONNECT_FAILED        = -24;
    const ERR_EXTENSION_NOT_FOUND   = -25;
    const ERR_NOSUCHDB              = -26;
    const ERR_ACCESS_VIOLATION      = -27;
    const ERR_CANNOT_REPLACE        = -28;
    const ERR_CONSTRAINT_NOT_NULL   = -29;
    const ERR_DEADLOCK              = -30;
    const ERR_CANNOT_ALTER          = -31;
    const ERR_MANAGER               = -32;
    const ERR_MANAGER_PARSE         = -33;
    const ERR_LOADMODULE            = -34;
    const ERR_INSUFFICIENT_DATA     = -35;
    const ERR_CLASS_NAME            = -36;

    /**
     * PDO derived constants
     */
    const CASE_LOWER = 2;
    const CASE_NATURAL = 0;
    const CASE_UPPER = 1;
    const CURSOR_FWDONLY = 0;
    const CURSOR_SCROLL = 1;
    const ERRMODE_EXCEPTION = 2;
    const ERRMODE_SILENT = 0;
    const ERRMODE_WARNING = 1;
    const FETCH_ASSOC = 2;
    const FETCH_BOTH = 4;
    const FETCH_BOUND = 6;
    const FETCH_CLASS = 8;
    const FETCH_CLASSTYPE = 262144;
    const FETCH_COLUMN = 7;
    const FETCH_FUNC = 10;
    const FETCH_GROUP = 65536;
    const FETCH_INTO = 9;
    const FETCH_LAZY = 1;
    const FETCH_NAMED = 11;
    const FETCH_NUM = 3;
    const FETCH_OBJ = 5;
    const FETCH_ORI_ABS = 4;
    const FETCH_ORI_FIRST = 2;
    const FETCH_ORI_LAST = 3;
    const FETCH_ORI_NEXT = 0;
    const FETCH_ORI_PRIOR = 1;
    const FETCH_ORI_REL = 5;
    const FETCH_SERIALIZE = 524288;
    const FETCH_UNIQUE = 196608;
    const NULL_EMPTY_STRING = 1;
    const NULL_NATURAL = 0;
    const NULL_TO_STRING         = NULL;
    const PARAM_BOOL = 5;
    const PARAM_INPUT_OUTPUT = -2147483648;
    const PARAM_INT = 1;
    const PARAM_LOB = 3;
    const PARAM_NULL = 0;
    const PARAM_STMT = 4;
    const PARAM_STR = 2;

    /**
     * ATTRIBUTE CONSTANTS
     */

    /**
     * PDO derived attributes
     */
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
     * Doctrine constants
     */
    const ATTR_LISTENER             = 100;
    const ATTR_QUOTE_IDENTIFIER     = 101;
    const ATTR_FIELD_CASE           = 102;
    const ATTR_IDXNAME_FORMAT       = 103;
    const ATTR_SEQNAME_FORMAT       = 104;
    const ATTR_SEQCOL_NAME          = 105;
    const ATTR_CMPNAME_FORMAT       = 118;
    const ATTR_DBNAME_FORMAT        = 117;
    const ATTR_TBLCLASS_FORMAT      = 119;
    const ATTR_TBLNAME_FORMAT       = 120;
    const ATTR_EXPORT               = 140;
    const ATTR_DECIMAL_PLACES       = 141;

    const ATTR_PORTABILITY          = 106;
    const ATTR_VALIDATE             = 107;
    const ATTR_COLL_KEY             = 108;
    const ATTR_QUERY_LIMIT          = 109;
    const ATTR_DEFAULT_TABLE_TYPE   = 112;
    const ATTR_DEF_TEXT_LENGTH      = 113;
    const ATTR_DEF_VARCHAR_LENGTH   = 114;
    const ATTR_DEF_TABLESPACE       = 115;
    const ATTR_EMULATE_DATABASE     = 116;
    const ATTR_USE_NATIVE_ENUM      = 117;
    const ATTR_DEFAULT_SEQUENCE     = 133;

    const ATTR_FETCHMODE                = 118;
    const ATTR_NAME_PREFIX              = 121;
    const ATTR_CREATE_TABLES            = 122;
    const ATTR_COLL_LIMIT               = 123;

    const ATTR_CACHE                    = 150;
    const ATTR_RESULT_CACHE             = 150;
    const ATTR_CACHE_LIFESPAN           = 151;
    const ATTR_RESULT_CACHE_LIFESPAN    = 151;
    const ATTR_LOAD_REFERENCES          = 153;
    const ATTR_RECORD_LISTENER          = 154;
    const ATTR_THROW_EXCEPTIONS         = 155;
    const ATTR_DEFAULT_PARAM_NAMESPACE  = 156;
    const ATTR_QUERY_CACHE              = 157;
    const ATTR_QUERY_CACHE_LIFESPAN     = 158;
    const ATTR_AUTOLOAD_TABLE_CLASSES   = 160;
    const ATTR_MODEL_LOADING            = 161;

    /**
     * LIMIT CONSTANTS
     */

    /**
     * constant for row limiting
     */
    const LIMIT_ROWS       = 1;

    /**
     * constant for record limiting
     */
    const LIMIT_RECORDS    = 2;

    /**
     * FETCHMODE CONSTANTS
     */

    /**
     * IMMEDIATE FETCHING
     * mode for immediate fetching
     */
    const FETCH_IMMEDIATE       = 0;

    /**
     * BATCH FETCHING
     * mode for batch fetching
     */
    const FETCH_BATCH           = 1;

    /**
     * LAZY FETCHING
     * mode for offset fetching
     */
    const FETCH_OFFSET          = 3;

    /**
     * LAZY OFFSET FETCHING
     * mode for lazy offset fetching
     */
    const FETCH_LAZY_OFFSET     = 4;

    /**
     * FETCH CONSTANTS
     */


    /**
     * FETCH VALUEHOLDER
     */
    const FETCH_VHOLDER         = 1;

    /**
     * FETCH RECORD
     *
     * Specifies that the fetch method shall return Doctrine_Record
     * objects as the elements of the result set.
     *
     * This is the default fetchmode.
     */
    const FETCH_RECORD          = 2;

    /**
     * FETCH ARRAY
     */
    const FETCH_ARRAY           = 3;

    /**
     * PORTABILITY CONSTANTS
     */

    /**
     * Portability: turn off all portability features.
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_NONE          = 0;

    /**
     * Portability: convert names of tables and fields to case defined in the
     * "field_case" option when using the query*(), fetch*() methods.
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_CASE      = 1;

    /**
     * Portability: right trim the data output by query*() and fetch*().
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_RTRIM         = 2;

    /**
     * Portability: force reporting the number of rows deleted.
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_DELETE_COUNT  = 4;

    /**
     * Portability: convert empty values to null strings in data output by
     * query*() and fetch*().
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_EMPTY_TO_NULL = 8;

    /**
     * Portability: removes database/table qualifiers from associative indexes
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_ASSOC_FIELD_NAMES = 16;

    /**
     * Portability: makes Doctrine_Expression throw exception for unportable RDBMS expressions
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_EXPR          = 32;

    /**
     * Portability: turn on all portability features.
     * @see self::ATTR_PORTABILITY
     */
    const PORTABILITY_ALL           = 63;

    /**
     * LOCKMODE CONSTANTS
     */

    /**
     * mode for optimistic locking
     */
    const LOCK_OPTIMISTIC       = 0;

    /**
     * mode for pessimistic locking
     */
    const LOCK_PESSIMISTIC      = 1;

    /**
     * EXPORT CONSTANTS
     */

    /**
     * EXPORT_NONE
     */
    const EXPORT_NONE               = 0;

    /**
     * EXPORT_TABLES
     */
    const EXPORT_TABLES             = 1;

    /**
     * EXPORT_CONSTRAINTS
     */
    const EXPORT_CONSTRAINTS        = 2;

    /**
     * EXPORT_PLUGINS
     */
    const EXPORT_PLUGINS            = 4;

    /**
     * EXPORT_ALL
     */
    const EXPORT_ALL                = 7;

    /**
     * HYDRATION CONSTANTS
     */
    const HYDRATE_RECORD            = 2;

    /**
     * HYDRATE_ARRAY
     */
    const HYDRATE_ARRAY             = 3;

    /**
     * HYDRATE_NONE
     */
    const HYDRATE_NONE              = 4;

    /**
     * VALIDATION CONSTANTS
     */
    const VALIDATE_NONE             = 0;

    /**
     * VALIDATE_LENGTHS
     */
    const VALIDATE_LENGTHS          = 1;

    /**
     * VALIDATE_TYPES
     */
    const VALIDATE_TYPES            = 2;

    /**
     * VALIDATE_CONSTRAINTS
     */
    const VALIDATE_CONSTRAINTS      = 4;

    /**
     * VALIDATE_ALL
     */
    const VALIDATE_ALL              = 7;

    /**
     * IDENTIFIER_AUTOINC
     *
     * constant for auto_increment identifier
     */
    const IDENTIFIER_AUTOINC        = 1;

    /**
     * IDENTIFIER_SEQUENCE
     *
     * constant for sequence identifier
     */
    const IDENTIFIER_SEQUENCE       = 2;

    /**
     * IDENTIFIER_NATURAL
     *
     * constant for normal identifier
     */
    const IDENTIFIER_NATURAL        = 3;

    /**
     * IDENTIFIER_COMPOSITE
     *
     * constant for composite identifier
     */
    const IDENTIFIER_COMPOSITE      = 4;

    /**
     * MODEL_LOADING_AGRESSIVE
     *
     * Constant for agressive model loading
     * Will require_once() all found model files
     */
    const MODEL_LOADING_AGRESSIVE   = 1;

    /**
     * MODEL_LOADING_CONSERVATIVE
     *
     * Constant for conservative model loading
     * Will not require_once() found model files inititally instead it will build an array
     * and reference it in autoload() when a class is needed it will require_once() it
     */
    const MODEL_LOADING_CONSERVATIVE= 2;
    
    /**
     * Path
     *
     * @var string $path            doctrine root directory
     */
    private static $_path;

    /**
     * Debug
     *
     * Bool true/false
     *
     * @var boolean $_debug
     */
    private static $_debug = false;

    /**
     * _loadedModelFiles
     *
     * Array of all the loaded models and the path to each one for autoloading
     *
     * @var array
     */
    private static $_loadedModelFiles = array();

    /**
     * _validators
     *
     * Array of all the loaded validators
     * @var array
     */
    private static $_validators = array();

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

    /**
     * debug
     *
     * @param string $bool
     * @return void
     */
    public static function debug($bool = null)
    {
        if ($bool !== null) {
            self::$_debug = (bool) $bool;
        }

        return self::$_debug;
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
     * @param bool   $aggressive   Bool true/false for whether to load models aggressively.
     *                             If true it will require_once() all found .php files
     * @return array $loadedModels
     */
    public static function loadModels($directory, $agressive = true)
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
                        
                        if ($manager->getAttribute(Doctrine::ATTR_MODEL_LOADING) == Doctrine::MODEL_LOADING_CONSERVATIVE) {
                            self::$_loadedModelFiles[$e[0]] = $file->getPathName();
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
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return self::filterInvalidModels($loadedModels);
    }

    /**
     * getLoadedModels
     *
     * Get all the loaded models, you can provide an array of classes or it will use get_declared_classes()
     *
     * Will filter through an array of classes and return the Doctrine_Records out of them.
     * If you do not specify $classes it will return all of the currently loaded Doctrine_Records
     *
     * @param classes  Array of classes to filter through, otherwise uses get_declared_classes()
     * @return array   $loadedModels
     */
    public static function getLoadedModels($classes = null)
    {
        if ($classes === null) {
            $classes = get_declared_classes();
            $classes = array_merge($classes, array_keys(self::$_loadedModelFiles));
        }

        return self::filterInvalidModels($classes);
    }

    /**
     * filterInvalidModels
     *
     * Filter through an array of classes and return all the classes that are valid models
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
     * Checks if what is passed is a valid Doctrine_Record
     *
     * @param   mixed   $class Can be a string named after the class, an instance of the class, or an instance of the class reflected
     * @return  boolean
     */
    public static function isValidModelClass($class)
    {
        if ($class instanceof Doctrine_Record) {
            $class = get_class($class);
        }

        if (is_string($class) && class_exists($class)) {
            $class = new ReflectionClass($class);
        }

        if ($class instanceof ReflectionClass) {
            // Skip the following classes
            // - abstract classes
            // - not a subclass of Doctrine_Record
            // - don't have a setTableDefinition method
            if (!$class->isAbstract() &&
                $class->isSubClassOf('Doctrine_Record') &&
                $class->hasMethod('setTableDefinition')) {

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
     * method for importing existing schema to Doctrine_Record classes
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
        $directory = '/tmp/tmp_doctrine_models';

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
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $manager = Doctrine_Manager::getInstance();
        $connections = $manager->getConnections();

        $results = array();

        foreach ($connections as $name => $connection) {
            if ( ! empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            $info = $manager->parsePdoDsn($connection->getOption('dsn'));
            $username = $connection->getOption('username');
            $password = $connection->getOption('password');

            // Make connection without database specified so we can create it
            $connect = $manager->openConnection(new PDO($info['scheme'] . ':host=' . $info['host'], $username, $password), 'tmp_connection', false);

            try {
                // Create database
                $connect->export->createDatabase($name);

                // Close the tmp connection with no database
                $manager->closeConnection($connect);

                // Close original connection
                $manager->closeConnection($connection);

                // Reopen original connection with newly created database
                $manager->openConnection(new PDO($info['dsn'], $username, $password), $name, true);

                $results[$name] = true;
            } catch (Exception $e) {
                $results[$name] = false;
            }
        }

        return $results;
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
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $manager = Doctrine_Manager::getInstance();

        $connections = $manager->getConnections();

        $results = array();

        foreach ($connections as $name => $connection) {
            if ( ! empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            try {
                $connection->export->dropDatabase($name);

                $results[$name] = true;
            } catch (Exception $e) {
                $results[$name] = false;
            }
        }

        return $results;
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
        $builder = new Doctrine_Migration_Builder($migrationsPath);

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
        $builder = new Doctrine_Migration_Builder($migrationsPath);

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
        $builder = new Doctrine_Migration_Builder($migrationsPath);

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
     * fileFinder
     *
     * @param string $type
     * @return void
     */
    public static function fileFinder($type)
    {
        return Doctrine_FileFinder::type($type);
    }

    /**
     * compile
     *
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @param string $target
     * @param array  $includedDrivers
     * @throws Doctrine_Exception
     * @return void
     */
    public static function compile($target = null, $includedDrivers = array())
    {
        return Doctrine_Compiler::compile($target, $includedDrivers);
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

        $loadedModels = self::$_loadedModelFiles;

        if (isset($loadedModels[$className]) && file_exists($loadedModels[$className])) {
            require_once $loadedModels[$className];

            return true;
        }

        return false;
    }

    /**
     * dump
     *
     * dumps a given variable
     *
     * @param mixed $var        a variable of any type
     * @param boolean $output   whether to output the content
     * @param string $indent    indention string
     * @return void|string
     */
    public static function dump($var, $output = true, $indent = "")
    {
        $ret = array();
        switch (gettype($var)) {
            case 'array':
                $ret[] = 'Array(';
                $indent .= "    ";
                foreach ($var as $k => $v) {

                    $ret[] = $indent . $k . ' : ' . self::dump($v, false, $indent);
                }
                $indent = substr($indent,0, -4);
                $ret[] = $indent . ")";
                break;
            case 'object':
                $ret[] = 'Object(' . get_class($var) . ')';
                break;
            default:
                $ret[] = var_export($var, true);
        }

        if ($output) {
            print implode("\n", $ret);
        }

        return implode("\n", $ret);
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
         return Doctrine_Inflector::tableize($className);
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
        return Doctrine_Inflector::classify($tableName);
    }

    /**
     * isValidClassName
     *
     * checks for valid class name (uses camel case and underscores)
     *
     * @param string $classname
     * @return boolean
     */
    public static function isValidClassname($className)
    {
        return Doctrine_Lib::isValidClassName($className);
    }
}
