<?php
require_once("Configurable.class.php");
require_once("EventListener.class.php");
/**
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Manager extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * @var array $session      an array containing all the opened sessions
     */
    private $sessions   = array();
    /**
     * @var integer $index
     */
    private $index      = 0;
    /**
     * @var integer $currIndex
     */
    private $currIndex  = 0;
    /**
     * @var string $root
     */
    private $root;


    /**
     * constructor
     */
    private function __construct() {
        $this->root = dirname(__FILE__);
    }
    /**
     * setDefaultAttributes
     */
    final public function setDefaultAttributes() {
        static $init = false;
        if( ! $init) {
            $init = true;
            $attributes = array(
                        Doctrine::ATTR_CACHE_DIR        => "%ROOT%".DIRECTORY_SEPARATOR."cachedir",
                        Doctrine::ATTR_FETCHMODE        => Doctrine::FETCH_LAZY,
                        Doctrine::ATTR_CACHE_TTL        => 100,
                        Doctrine::ATTR_CACHE_SIZE       => 100,
                        Doctrine::ATTR_CACHE            => Doctrine::CACHE_FILE,
                        Doctrine::ATTR_BATCH_SIZE       => 5,
                        Doctrine::ATTR_LISTENER         => new EmptyEventListener(),
                        Doctrine::ATTR_PK_COLUMNS       => array("id"),
                        Doctrine::ATTR_PK_TYPE          => Doctrine::INCREMENT_KEY,
                        Doctrine::ATTR_LOCKMODE         => 1,
                        Doctrine::ATTR_VLD              => false,
                        Doctrine::ATTR_CREATE_TABLES    => true
                        );
            foreach($attributes as $attribute => $value) {
                $old = $this->getAttribute($attribute);
                if($old === null)
                    $this->setAttribute($attribute,$value);
            }
        }
    }
    /**
     * @return string       the root directory of Doctrine
     */
    final public function getRoot() {
        return $this->root;
    }
    /**
     * getInstance                  this class uses the singleton pattern
     */
    final public static function getInstance() {
        static $instance;
        if( ! isset($instance))
            $instance = new Doctrine_Manager();

        return $instance;
    }

    /**
     * openSession                  open a new session and save it to Doctrine_Manager->sessions
     * @param PDO $pdo              PDO database driver
     * @param string $name          name of the session, if empty numeric key is used
     * @return Doctrine_Session           the opened session object
     */
    final public function openSession(PDO $pdo, $name = null) {
        // initialize the default attributes
        $this->setDefaultAttributes();

        if($name !== null) {
            $name = (string) $name;
            if(isset($this->sessions[$name]))
                throw new InvalidKeyException();
        
        } else {
            $name = $this->index;
            $this->index++;
        }
        switch($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)):
            case "mysql":
                $this->sessions[$name] = new Doctrine_Session_Mysql($this,$pdo);
            break;
            case "sqlite":
                $this->sessions[$name] = new Doctrine_Session_Sqlite($this,$pdo);
            break;
            case "pgsql":
                $this->sessions[$name] = new Doctrine_Session_Pgsql($this,$pdo);
            break;
            case "oci":
                $this->sessions[$name] = new Doctrine_Session_Oracle($this,$pdo);
            break;
            case "mssql":
                $this->sessions[$name] = new Doctrine_Session_Mssql($this,$pdo);
            break;
            case "firebird":
                $this->sessions[$name] = new Doctrine_Session_Firebird($this,$pdo);
            break;
            case "informix":
                $this->sessions[$name] = new Doctrine_Session_Informix($this,$pdo);
            break;
        endswitch;


        $this->currIndex = $name;
        return $this->sessions[$name];
    }
    /**
     * getSession
     * @param integer $index
     * @return object Doctrine_Session
     * @throws InvalidKeyException
     */
    final public function getSession($index) {
        if( ! isset($this->sessions[$index]))
            throw new InvalidKeyException();

        $this->currIndex = $index;
        return $this->sessions[$index];
    }
    /**
     * closes the session
     *
     * @param Doctrine_Session $session
     * @return void
     */
    final public function closeSession(Doctrine_Session $session) {
        $session->close();
        unset($session);
    }
    /**
     * getSessions
     * @return array
     */
    final public function getSessions() {
        return $this->sessions;
    }
    /**
     * setCurrentSession
     * @param mixed $key                the session key
     * @throws InvalidKeyException
     * @return void
     */
    final public function setCurrentSession($key) {
        $key = (string) $key;
        if( ! isset($this->sessions[$key]))
            throw new InvalidKeyException();
        
        $this->currIndex = $key;
    }
    /**
     * count
     * @return integer                  the number of open sessions
     */
    public function count() {
        return count($this->sessions);
    }
    /**
     * getIterator
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->sessions);
    }
    /**
     * getCurrentSession
     * @return object Doctrine_Session
     */
    final public function getCurrentSession() {
        $i = $this->currIndex;
        if( ! isset($this->sessions[$i]))
            throw new Doctrine_Session_Exception();

        return $this->sessions[$i];
    }
    /**
     * __toString
     */
    public function __toString() {
        $r[] = "<pre>";
        $r[] = "Doctrine_Manager";
        $r[] = "Sessions : ".count($this->sessions);
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
?>
