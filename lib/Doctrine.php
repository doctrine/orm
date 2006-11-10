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

require_once("Doctrine/Exception.php");

/**
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen
 * @license     LGPL
 */
final class Doctrine {
    /**
     * error constants
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

    /**
     * ATTRIBUTE CONSTANTS
     */

    /**
     * event listener attribute
     */
    const ATTR_LISTENER         = 1;
    /**
     * fetchmode attribute
     */
    const ATTR_FETCHMODE        = 2;
    /**
     * cache directory attribute
     */
    const ATTR_CACHE_DIR        = 3;
    /**
     * cache time to live attribute
     */
    const ATTR_CACHE_TTL        = 4;
    /**
     * cache size attribute
     */
    const ATTR_CACHE_SIZE       = 5;
    /**
     * cache slam defense probability
     */
    const ATTR_CACHE_SLAM       = 6;
    /**
     * cache container attribute
     */
    const ATTR_CACHE            = 7;
    /**
     * batch size attribute
     */
    const ATTR_BATCH_SIZE       = 8;
    /**
     * locking attribute
     */
    const ATTR_LOCKMODE         = 11;
    /**
     * validatate attribute
     */
    const ATTR_VLD              = 12;
    /**
     * name prefix attribute
     */
    const ATTR_NAME_PREFIX      = 13;
    /**
     * create tables attribute
     */
    const ATTR_CREATE_TABLES    = 14;
    /**
     * collection key attribute
     */
    const ATTR_COLL_KEY         = 15;
    /**
     * collection limit attribute
     */
    const ATTR_COLL_LIMIT       = 16;
    /**
     * query limit
     */
    const ATTR_QUERY_LIMIT      = 17;
    /**
     * accessor invoking attribute
     */
    const ATTR_ACCESSORS        = 18;
    /**
     * automatic length validations attribute
     */
    const ATTR_AUTO_LENGTH_VLD  = 19;
    /**
     * automatic type validations attribute
     */
    const ATTR_AUTO_TYPE_VLD    = 20;
    /**
     * short aliases attribute
     */
    const ATTR_SHORT_ALIASES    = 21;
    
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
     * mode for lazy fetching
     */
    const FETCH_LAZY            = 2;
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
     * ACCESSOR CONSTANTS
     */
    
    /**
     * constant for get accessors
     */
    const ACCESSOR_GET          = 1;
    /**
     * constant for set accessors
     */
    const ACCESSOR_SET          = 2;
    /**
     * constant for both accessors get and set
     */
    const ACCESSOR_BOTH         = 4;


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
     * constructor
     */
    public function __construct() {
        throw new Doctrine_Exception('Doctrine is static class. No instances can be created.');
    }
    /**
     * @var string $path            doctrine root directory
     */
    private static $path;
    /**
     * getPath
     * returns the doctrine root
     *
     * @return string
     */
    public static function getPath() {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        return self::$path;
    }
    /**
     * loadAll
     * loads all runtime classes
     *
     * @return void
     */
    public static function loadAll() {
        $classes = Doctrine_Compiler::getRuntimeClasses();

        foreach($classes as $class) {
            Doctrine::autoload($class);
        }
    }
    /**
     * import
     * method for importing existing schema to Doctrine_Record classes
     *
     * @param string $directory
     */
    public static function import($directory) {

    }
    /**
     * export
     * method for exporting Doctrine_Record classes to a schema
     *
     * @param string $directory
     */
    public static function export($directory) {
                                              	
    }                                          	
    /**
     * compile
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @throws Doctrine_Exception
     * @return void
     */
    public static function compile() {
        Doctrine_Compiler::compile();
    }
    /**
     * simple autoload function
     * returns true if the class was loaded, otherwise false
     *
     * @param string $classname
     * @return boolean
     */
    public static function autoload($classname) {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        if(class_exists($classname))
            return false;

        $class = self::$path.DIRECTORY_SEPARATOR.str_replace("_",DIRECTORY_SEPARATOR,$classname).".php";

        if( ! file_exists($class))
            return false;


        require_once($class);
        return true;
    }
    /**
     * returns table name from class name
     *
     * @param string $classname
     * @return string
     */
    public static function tableize($classname) {
         return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $classname));
    }
    /**
     * returns class name from table name
     *
     * @param string $tablename
     * @return string
     */
    public static function classify($tablename) {
        return preg_replace('~(_?)(_)([\w])~e', '"$1".strtoupper("$3")', ucfirst($tablename));
    }
}
?>
