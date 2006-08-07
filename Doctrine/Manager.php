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
require_once("Configurable.php");
require_once("EventListener.php");
/**
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * 
 * Doctrine_Manager is the base component of all doctrine based projects. 
 * It opens and keeps track of all sessions (database connections).
 */
class Doctrine_Manager extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * @var array $session          an array containing all the opened sessions
     */
    private $sessions   = array();
    /**
     * @var integer $index          the incremented index
     */
    private $index      = 0;
    /**
     * @var integer $currIndex      the current session index
     */
    private $currIndex  = 0;
    /**
     * @var string $root            root directory
     */
    private $root; 
    /**
     * @var Doctrine_Null $null     Doctrine_Null object, used for extremely fast null value checking
     */
    private $null;

    /**
     * constructor
     */
    private function __construct() {
        $this->root = dirname(__FILE__);
        $this->null = new Doctrine_Null;

        Doctrine_Record::initNullObject($this->null);
        Doctrine_Collection::initNullObject($this->null);
        Doctrine_Record_Iterator::initNullObject($this->null);
        Doctrine_Validator::initNullObject($this->null);
    }
    /**
     * @return Doctrine_Null
     */
    final public function getNullObject() {
        return $this->null;
    }
    /**
     * setDefaultAttributes
     * sets default attributes
     *
     * @return boolean
     */
    final public function setDefaultAttributes() {
        static $init = false;
        if( ! $init) {
            $init = true;
            $attributes = array(
                        Doctrine::ATTR_CACHE_DIR        => "%ROOT%".DIRECTORY_SEPARATOR."cachedir",
                        Doctrine::ATTR_FETCHMODE        => Doctrine::FETCH_IMMEDIATE,
                        Doctrine::ATTR_CACHE_TTL        => 100,
                        Doctrine::ATTR_CACHE_SIZE       => 100,
                        Doctrine::ATTR_CACHE            => Doctrine::CACHE_NONE,
                        Doctrine::ATTR_BATCH_SIZE       => 5,
                        Doctrine::ATTR_COLL_LIMIT       => 5,
                        Doctrine::ATTR_LISTENER         => new Doctrine_EventListener_Empty(),
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
            return true;
        }
        return false;
    }
    /**
     * returns the root directory of Doctrine
     *
     * @return string
     */
    final public function getRoot() {
        return $this->root;
    }
    /**
     * getInstance                  
     * returns an instance of this class
     * (this class uses the singleton pattern)
     *
     * @return Doctrine_Manager
     */
    final public static function getInstance() {
        static $instance;
        if( ! isset($instance))
            $instance = new self();

        return $instance;
    }
    /**
     * install
     * 
     * @return void
     */
    final public function install() {
        $parent = new ReflectionClass('Doctrine_Record');
        $old    = $this->getAttribute(Doctrine::ATTR_CREATE_TABLES);

        $this->attributes[Doctrine::ATTR_CREATE_TABLES] = true;
        foreach(get_declared_classes() as $name) {
            $class = new ReflectionClass($name);

            if($class->isSubclassOf($parent))
                $obj = new $class();
        }
        $this->attributes[Doctrine::ATTR_CREATE_TABLES] = $old;
    }
    /**
     * openSession                          
     * opens a new session and saves it to Doctrine_Manager->sessions
     *
     * @param PDO $pdo                      PDO database driver
     * @param string $name                  name of the session, if empty numeric key is used
     * @return Doctrine_Session
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
     * returns all opened sessions
     *
     * @return array
     */
    final public function getSessions() {
        return $this->sessions;
    }
    /**
     * setCurrentSession
     * sets the current session to $key
     *
     * @param mixed $key                        the session key
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
     * returns the number of opened sessions
     *
     * @return integer
     */
    public function count() {
        return count($this->sessions);
    }
    /**
     * getIterator
     * returns an ArrayIterator that iterates through all sessions
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->sessions);
    }
    /**
     * getCurrentSession
     * returns the current session
     *
     * @throws Doctrine_Session_Exception       if there are no open sessions
     * @return Doctrine_Session
     */
    final public function getCurrentSession() {
        $i = $this->currIndex;
        if( ! isset($this->sessions[$i]))
            throw new Doctrine_Session_Exception();

        return $this->sessions[$i];
    }
    /**
     * __toString
     * returns a string representation of this object
     *
     * @return string
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
