<?php
require_once("Exception.class.php");
/**
 * Doctrine
 * the base class of Doctrine framework
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
     * @var string $path            doctrine root directory
     */
    private static $path;
    
    public static function getPath() {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        return self::$path;
    }
    /**
     * loads all runtime classes
     */
    public static function loadAll() {
        if(! self::$path)
            self::$path = dirname(__FILE__);
            
        $dir = dir(self::$path);
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
                case "Session":
                case "DQL":
                case "Sensei":
                    $a[]  = self::$path.DIRECTORY_SEPARATOR.$entry;
                break;
                default:
                    if(is_file(self::$path.DIRECTORY_SEPARATOR.$entry) && substr($entry,-4) == ".php") {
                        require_once($entry);
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
     * simple autoload function
     */
    public static function autoload($classname) {
        if(! self::$path)
            self::$path = dirname(__FILE__);

        $e = explode("_",$classname);

        if($e[0] != "Doctrine")
            return false;

        if(end($e) != "Exception") {
            if(count($e) > 2) {
                array_shift($e);
                $dir = array_shift($e);
                $class = self::$path.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.implode('',$e).".class.php";
            } elseif(count($e) > 1) {
                $class = self::$path.DIRECTORY_SEPARATOR.$e[1].".class.php";
            } else
                return false;
        } else {
            $class = self::$path.DIRECTORY_SEPARATOR."Exception".DIRECTORY_SEPARATOR.$e[1].".class.php";
        }

        if( ! file_exists($class)) {
            return false;
        }

        require_once($class);
        return true;
    }
}
?>
