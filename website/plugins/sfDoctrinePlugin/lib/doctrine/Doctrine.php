<?php
/*
 *  $Id: Doctrine.php 2255 2007-08-16 22:42:35Z Jonathan.Wage $
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
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2255 $
 */
final class Doctrine
{
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
    const NULL_TO_STRING		 = NULL;
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
    const ATTR_EXPORT               = 140;
    const ATTR_DECIMAL_PLACES       = 141;  

    const ATTR_PORTABILITY          = 106;
    const ATTR_VLD                  = 107;
    const ATTR_COLL_KEY             = 108;
    const ATTR_QUERY_LIMIT          = 109;
    const ATTR_AUTO_LENGTH_VLD      = 110;
    const ATTR_AUTO_TYPE_VLD        = 111;
    const ATTR_DEFAULT_TABLE_TYPE   = 112;
    const ATTR_DEF_TEXT_LENGTH      = 113;
    const ATTR_DEF_VARCHAR_LENGTH   = 114;
    const ATTR_DEF_TABLESPACE       = 115;
    const ATTR_EMULATE_DATABASE     = 116;
    const ATTR_DEFAULT_SEQUENCE     = 133;

    /** TODO: REMOVE THE FOLLOWING CONSTANTS AND UPDATE THE DOCS ! */


    const ATTR_FETCHMODE            = 118;
    const ATTR_BATCH_SIZE           = 119;
    const ATTR_LOCKMODE             = 120;
    const ATTR_NAME_PREFIX          = 121;
    const ATTR_CREATE_TABLES        = 122;
    const ATTR_COLL_LIMIT           = 123;
    const ATTR_ACCESSORS            = 124;
    const ATTR_ACCESSOR_PREFIX_GET  = 125;
    const ATTR_ACCESSOR_PREFIX_SET  = 126;

    /**
     * NESTED SET CONSTANTS
     */
    const ATTR_NS_ROOT_COLUMN_NAME  = 130;
    const ATTR_NS_GAP_SIZE          = 131;
    const ATTR_NS_GAP_DECREASE_EXP  = 132;

    const ATTR_CACHE                = 150;
    const ATTR_CACHE_LIFESPAN       = 151;
    const ATTR_LOAD_REFERENCES      = 153;
    const ATTR_RECORD_LISTENER      = 154;
    const ATTR_THROW_EXCEPTIONS     = 155;


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
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_NONE          = 0;
    /**
     * Portability: convert names of tables and fields to case defined in the
     * "field_case" option when using the query*(), fetch*() methods.
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_CASE      = 1;

    /**
     * Portability: right trim the data output by query*() and fetch*().
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_RTRIM         = 2;
    /**
     * Portability: force reporting the number of rows deleted.
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_DELETE_COUNT  = 4;
    /**
     * Portability: convert empty values to null strings in data output by
     * query*() and fetch*().
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_EMPTY_TO_NULL = 8;
    /**
     * Portability: removes database/table qualifiers from associative indexes
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_FIX_ASSOC_FIELD_NAMES = 16;
    /**
     * Portability: makes Doctrine_Expression throw exception for unportable RDBMS expressions
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_EXPR          = 32;
    /**
     * Portability: turn on all portability features.
     * @see Doctrine::ATTR_PORTABILITY
     */
    const PORTABILITY_ALL           = 33;

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
     * turns of exporting
     */
    const EXPORT_NONE               = 0;
    /**
     * export tables
     */
    const EXPORT_TABLES             = 1;
    /**
     * export constraints
     */
    const EXPORT_CONSTRAINTS        = 2;
    /**
     * export all
     */
    const EXPORT_ALL                = 3;


    /**
     * constant for auto_increment identifier
     */
    const IDENTIFIER_AUTOINC        = 1;
    /**
     * constant for sequence identifier
     */
    const IDENTIFIER_SEQUENCE       = 2;
    /**
     * constant for normal identifier
     */
    const IDENTIFIER_NATURAL        = 3;
    /**
     * constant for composite identifier
     */
    const IDENTIFIER_COMPOSITE      = 4;
    /**
     * constructor
     */
    public function __construct()
    {
        throw new Doctrine_Exception('Doctrine is static class. No instances can be created.');
    }
    /**
     * @var string $path            doctrine root directory
     */
    private static $path;
    /**
     * @var boolean $_debug
     */
    private static $_debug = false;

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
        if ( ! self::$path) {
            self::$path = dirname(__FILE__);
        }
        return self::$path;
    }
    /**
     * loadAll
     * loads all runtime classes
     *
     * @return void
     */
    public static function loadAll()
    {
        $classes = Doctrine_Compiler::getRuntimeClasses();

        foreach ($classes as $class) {
            Doctrine::autoload($class);
        }
    }
    /**
     * importSchema
     * method for importing existing schema to Doctrine_Record classes
     *
     * @param string $directory
     * @param array $info
     * @return boolean
     */
    public static function importSchema($directory, array $databases = array())
    {
        return Doctrine_Manager::connection()->import->importSchema($directory, $databases);
    }
    /**
     * exportSchema
     * method for exporting Doctrine_Record classes to a schema
     *
     * @param string $directory
     */
    public static function exportSchema($directory = null)
    {
        return Doctrine_Manager::connection()->export->exportSchema($directory);
    }
    /**
     * exportSql
     * method for exporting Doctrine_Record classes to a schema
     *
     * @param string $directory
     */
    public static function exportSql($directory = null)
    {
        return Doctrine_Manager::connection()->export->exportSql($directory);
    }
    /**
     * compile
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @param string $target
     *
     * @throws Doctrine_Exception
     * @return void
     */
    public static function compile($target = null)
    {
        Doctrine_Compiler::compile($target);
    }
    /**
     * simple autoload function
     * returns true if the class was loaded, otherwise false
     *
     * @param string $classname
     * @return boolean
     */
    public static function autoload($classname)
    {  
        if (class_exists($classname, false)) {
            return false;
        }
        if (! self::$path) {
            self::$path = dirname(__FILE__);
        }
        $class = self::$path . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR,$classname) . '.php';

        if ( ! file_exists($class)) {
            return false;
        }

        require_once($class);

        return true;
    }
    /**
     * dump
     *
     * dumps a given variable
     *
     * @param mixed $var        a variable of any type
     * @param boolean $output   whether to output the content
     * @return void|string
     */
    public static function dump($var, $output = true)
    {
    	$ret = array();
        switch (gettype($var)) {
            case 'array':
                $ret[] = 'Array(';
                foreach ($var as $k => $v) {
                    $ret[] = $k . ' : ' . Doctrine::dump($v, false);
                }
                $ret[] = ")";
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
     * returns table name from class name
     *
     * @param string $classname
     * @return string
     */
    public static function tableize($classname)
    {
         return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $classname));
    }
    /**
     * returns class name from table name
     *
     * @param string $tablename
     * @return string
     */
    public static function classify($tablename)
    {
        return preg_replace('~(_?)(_)([\w])~e', '"$1".strtoupper("$3")', ucfirst($tablename));
    }
    /**
     * checks for valid class name (uses camel case and underscores)
     *
     * @param string $classname
     * @return boolean
     */
    public static function isValidClassname($classname)
    {
        if (preg_match('~(^[a-z])|(_[a-z])|([\W])|(_{2})~', $classname)) {
            return false;
        }    

        return true;
    }
}
?>
