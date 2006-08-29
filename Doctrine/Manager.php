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
 * It opens and keeps track of all connections (database connections).
 */
class Doctrine_Manager extends Doctrine_Configurable implements Countable, IteratorAggregate {
    /**
     * @var array $connections      an array containing all the opened connections
     */
    private $connections   = array();
    /**
     * @var integer $index          the incremented index
     */
    private $index      = 0;
    /**
     * @var integer $currIndex      the current connection index
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
                        Doctrine::ATTR_FETCHMODE        => Doctrine::FETCH_IMMEDIATE,
                        Doctrine::ATTR_CACHE            => Doctrine::CACHE_NONE,
                        Doctrine::ATTR_BATCH_SIZE       => 5,
                        Doctrine::ATTR_COLL_LIMIT       => 5,
                        Doctrine::ATTR_LISTENER         => new Doctrine_EventListener_Empty(),
                        Doctrine::ATTR_PK_COLUMNS       => array("id"),
                        Doctrine::ATTR_PK_TYPE          => Doctrine::INCREMENT_KEY,
                        Doctrine::ATTR_LOCKMODE         => 1,
                        Doctrine::ATTR_VLD              => false,
                        Doctrine::ATTR_CREATE_TABLES    => true,
                        Doctrine::ATTR_QUERY_LIMIT      => Doctrine::LIMIT_RECORDS
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
     * openConnection
     * opens a new connection and saves it to Doctrine_Manager->connections
     *
     * @param PDO $pdo                      PDO database driver
     * @param string $name                  name of the connection, if empty numeric key is used
     * @return Doctrine_Connection
     */
    public function openConnection(PDO $pdo, $name = null) {
        // initialize the default attributes
        $this->setDefaultAttributes();

        if($name !== null) {
            $name = (string) $name;
            if(isset($this->connections[$name]))
                throw new Doctrine_Exception("Connection with $name already exists!");
        
        } else {
            $name = $this->index;
            $this->index++;
        }
        switch($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)):
            case "mysql":
                $this->connections[$name] = new Doctrine_Connection_Mysql($this,$pdo);
            break;
            case "sqlite":
                $this->connections[$name] = new Doctrine_Connection_Sqlite($this,$pdo);
            break;
            case "pgsql":
                $this->connections[$name] = new Doctrine_Connection_Pgsql($this,$pdo);
            break;
            case "oci":
                $this->connections[$name] = new Doctrine_Connection_Oracle($this,$pdo);
            break;
            case "mssql":
                $this->connections[$name] = new Doctrine_Connection_Mssql($this,$pdo);
            break;
            case "firebird":
                $this->connections[$name] = new Doctrine_Connection_Firebird($this,$pdo);
            break;
            case "informix":
                $this->connections[$name] = new Doctrine_Connection_Informix($this,$pdo);
            break;
        endswitch;


        $this->currIndex = $name;
        return $this->connections[$name];
    }
    public function openSession(PDO $pdo, $name = null) {
        return $this->openConnection($pdo, $name);
    }
    /**
     * getConnection
     * @param integer $index
     * @return object Doctrine_Connection
     * @throws InvalidKeyException
     */
    public function getConnection($index) {
        if( ! isset($this->connections[$index]))
            throw new InvalidKeyException();

        $this->currIndex = $index;
        return $this->connections[$index];
    }
    public function getSession($index) { return $this->getConnection($index); }

    /**
     * closes the connection
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function closeConnection(Doctrine_Connection $connection) {
        $connection->close();
        unset($connection);
    }
    public function closeSession(Doctrine_Connection $connection) { $this->closeConnection($connection); }
    /**
     * getConnections
     * returns all opened connections
     *
     * @return array
     */
    public function getConnections() {
        return $this->connections;
    }
    public function getSessions() {
        return $this->connections;
    }
    /**
     * setCurrentConnection
     * sets the current connection to $key
     *
     * @param mixed $key                        the connection key
     * @throws InvalidKeyException
     * @return void
     */
    public function setCurrentConnection($key) {
        $key = (string) $key;
        if( ! isset($this->connections[$key]))
            throw new InvalidKeyException();
        
        $this->currIndex = $key;
    }
    public function setCurrentSession($key) {
        $this->setCurrentConnection($key);
    }
    /**
     * count
     * returns the number of opened connections
     *
     * @return integer
     */
    public function count() {
        return count($this->connections);
    }
    /**
     * getIterator
     * returns an ArrayIterator that iterates through all connections
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->connections);
    }
    /**
     * getCurrentConnection
     * returns the current connection
     *
     * @throws Doctrine_Connection_Exception       if there are no open connections
     * @return Doctrine_Connection
     */
    public function getCurrentConnection() {
        $i = $this->currIndex;
        if( ! isset($this->connections[$i]))
            throw new Doctrine_Connection_Exception();

        return $this->connections[$i];
    }
    public function getCurrentSession() { return $this->getCurrentConnection(); }
    /**
     * __toString
     * returns a string representation of this object
     *
     * @return string
     */
    public function __toString() {
        $r[] = "<pre>";
        $r[] = "Doctrine_Manager";
        $r[] = "Connections : ".count($this->connections);
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
?>
