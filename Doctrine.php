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
     * ERROR MODE CONSTANTS
     */

    /**
     * NO PRIMARY KEY COLUMN ERROR
     * no primary key column found error code
     */
    const ERR_NO_PK                 = 0;
    /**
     * PRIMARY KEY MISMATCH ERROR
     * this error code is used when user uses factory refresh for a
     * given Doctrine_Record and the old primary key doesn't match the new one
     */
    const ERR_REFRESH               = 1;
    /**
     * FIND ERROR
     * this code used when for example Doctrine_Table::find() is called and
     * a Data Access Object is not found
     */
    const ERR_FIND                  = 2;
    /**
     * TABLE NOT FOUND ERROR
     * this error code is used when user tries to initialize
     * a table and there is no database table for this factory
     */
    const ERR_NOSUCH_TABLE          = 3;
    /**
     * NAMING ERROR
     * this code is used when user defined Doctrine_Table is badly named
     */
    const ERR_NAMING                = 5;
    /**
     * TABLE INSTANCE ERROR
     * this code is used when user tries to initialize
     * a table that is already initialized
     */
    const ERR_TABLE_INSTANCE        = 6;
    /**
     * NO OPEN SESSIONS ERROR
     * error code which is used when user tries to get
     * current session are there are no sessions open
     */
    const ERR_NO_SESSIONS           = 7;
    /**
     * MAPPING ERROR
     * if there is something wrong with mapping logic
     * this error code is used
     */
    const ERR_MAPPING               = 8;

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
     * primary key columns attribute
     */
    const ATTR_PK_COLUMNS       = 9;
    /**
     * primary key type attribute
     */
    const ATTR_PK_TYPE          = 10;
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
     * CACHE CONSTANTS
     */

    /**
     * sqlite cache constant
     */
    const CACHE_SQLITE          = 0;
    /**
     * constant for disabling the caching
     */
    const CACHE_NONE            = 1;



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
     */
    const FETCH_RECORD          = 2;
    /**
     * FETCH ARRAY
     */
    const FETCH_ARRAY           = 3;


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
     * PRIMARY KEY TYPE CONSTANTS
     */

    /**
     * auto-incremented/(sequence updated) primary key
     */
    const INCREMENT_KEY         = 0;
    /**
     * unique key
     */
    const UNIQUE_KEY            = 1;

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
     * loads all runtime classes
     *
     * @return void
     */
    public static function loadAll() {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        $path = self::$path.DIRECTORY_SEPARATOR."Doctrine";
        $dir = dir($path);
        $a   = array();
        while (false !== ($entry = $dir->read())) {
            switch($entry):
                case ".":
                case "..":
                break;
                case "Cache":
                case "Record":
                case "Collection":
                case "Table":
                case "Validator":
                case "Exception":
                case "EventListener":
                case "Session":
                case "DQL":
                case "Sensei":
                case "Iterator":
                case "View":
                case "Query":
                    $a[]  = $path.DIRECTORY_SEPARATOR.$entry;
                break;
                default:
                    if(is_file($path.DIRECTORY_SEPARATOR.$entry) && substr($entry,-4) == ".php") {
                        require_once($path.DIRECTORY_SEPARATOR.$entry);
                    }
            endswitch;
        }
        foreach($a as $dirname) {
            $dir = dir($dirname);
            $path = $dirname.DIRECTORY_SEPARATOR;
            while (false !== ($entry = $dir->read())) {
                if(is_file($path.$entry) && substr($entry,-4) == ".php") {
                    require_once($path.$entry);
                }
            }
        }
    }
    /**
     * method for making a single file of most used doctrine components
     *
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by order of magnitude
     *
     * @throws Doctrine_Exception
     * @return void
     */
    public static function compile() {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        $classes = array("Doctrine",
                         "Configurable",
                         "Manager",
                         "Session",
                         "Table",
                         "Iterator",
                         "Exception",
                         "Access",
                         "Record",
                         "Record_Iterator",
                         "Collection",
                         "Validator",
                         "Hydrate",
                         "Query",
                         "Query_Part",
                         "Query_From",
                         "Query_Orderby",
                         "Query_Groupby",
                         "Query_Condition",
                         "Query_Where",
                         "Query_Having",
                         "RawSql",
                         "EventListener_Interface",
                         "EventListener",
                         "Relation",
                         "ForeignKey",
                         "LocalKey",
                         "Association",
                         "DB",
                         "DBStatement");


        $ret     = array();

        foreach($classes as $class) {
            if($class !== 'Doctrine')
                $class = 'Doctrine_'.$class;

            $file  = self::$path.DIRECTORY_SEPARATOR.str_replace("_",DIRECTORY_SEPARATOR,$class).".php";

            if( ! file_exists($file))
                throw new Doctrine_Exception("Couldn't compile $file. File $file does not exists.");

            self::autoload($class);
            $refl  = new ReflectionClass($class);
            $lines = file($file);

            $start = $refl->getStartLine() - 1;
            $end   = $refl->getEndLine() - 2;

            $i = 0;
            while($i < count($lines)) {
                $i++;

                if($i < $start)
                    continue;

                $ret[] = $lines[$i];

                if($i > $end)
                    break;
            }
        }


        $fp = fopen(self::$path.DIRECTORY_SEPARATOR.'Doctrine.compiled.php', 'w+');
        fwrite($fp, "<?php
".implode('', $ret)."
class InvalidKeyException extends Exception { }
class DQLException extends Exception { }
?>");
        fclose($fp);
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
        return  preg_replace('~(_?)(_)([\w])~e', '"$1".strtoupper("$3")', ucfirst($tablename));
    }
}
?>
