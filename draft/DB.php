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
 * Doctrine_DB
 * A thin layer on top of PDO
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_DB2 implements Countable, IteratorAggregate {
    /**
     * default DSN
     */
    const DSN = "mysql://root:dc34@localhost/test";
    /**
     * @var array $instances        all the instances of this class
     */
    protected static $instances   = array();
    /**
     * @var array $queries          all the executed queries
     */
    protected $queries            = array();
    /**
     * @var array $exectimes        execution times of the executed queries
     */
    protected $exectimes          = array();
    /**
     * @var array $isConnected      whether or not a connection has been established
     */
    protected $isConnected        = false;
    /**
     * @var string $dsn             data source name
     */
    protected $dsn;
    /**
     * @var string $username        database username
     */
    protected $username;
    /**
     * @var string $password        database password
     */
    protected $password;
    /**
     * @var PDO $dbh                the database handler
     */
    protected $dbh;
    /**
     * @var Doctrine_DB_EventListener_Interface|Doctrine_Overloadable $listener   listener for listening events
     */
    protected $listener;
    
    private static $driverMap = array("oracle"     => "oci8",
                                      "postgres"   => "pgsql",
                                      "oci"        => "oci8");


    /**
     * constructor
     *
     * @param string $dsn           data source name
     * @param string $username      database username
     * @param string $password      database password
     */
    public function __construct($dsn,$username,$password) {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->listener = new Doctrine_DB_EventListener();
    }
    /**
     * getDBH
     */
    public function getDBH() {
        return $this->dbh;
    }
    /**
     * getDSN
     * returns the data source name
     *
     * @return string
     */
    public function getDSN() {
        return $this->dsn;
    }
    /**
     * getUsername
     */
    public function getUsername() {
        return $this->username;
    }
    /**
     * getPassword
     */
    public function getPassword() {
        return $this->password;
    }
    /**
     * addListener
     *
     * @param Doctrine_DB_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_DB
     */
    public function addListener($listener, $name = null) {
        if( ! ($this->listener instanceof Doctrine_DB_EventListener_Chain))
            $this->listener = new Doctrine_DB_EventListener_Chain();

        $this->listener->add($listener, $name);
        
        return $this;
    }
    /**
     * getListener
     * 
     * @return Doctrine_DB_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener() {
        return $this->listener;
    }
    /**
     * setListener
     *
     * @param Doctrine_DB_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_DB
     */
    public function setListener($listener) {
        if( ! ($listener instanceof Doctrine_DB_EventListener_Interface) &&
            ! ($listener instanceof Doctrine_Overloadable))
            throw new Doctrine_DB_Exception("Couldn't set eventlistener for database handler. EventListeners should implement either Doctrine_DB_EventListener_Interface or Doctrine_Overloadable");

        $this->listener = $listener;

        return $this;
    }

    /**
     * connect
     * connects into database
     *
     * @return boolean
     */
    public function connect() {
        if($this->isConnected)
            return false;

        $this->dbh = new PDO($this->dsn,$this->username,$this->password);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("Doctrine_DB_Statement", array($this)));
        
        return true;
    }

    /**
     * getConnection
     *
     * @param string $dsn               PEAR::DB like DSN or PDO like DSN
     * format for PEAR::DB like DSN:    schema://user:password@address/dbname
     *
     * @return
     */
    public static function getConnection($dsn = null, $username = null, $password = null) {
        $md5 = md5($dsn);

        if( ! isset(self::$instances[$md5])) {
            if(isset($username)) {
                self::$instances[$md5] = new self($dsn, $username, $password);
            } else {
                if( ! isset($dsn))
                    $a = self::parseDSN(self::DSN);
                else
                    $a = self::parseDSN($dsn);

                extract($a);
    
                self::$instances[$md5] = new self($dsn, $user, $pass);
            }
        }
        return self::$instances[$md5];
    }
    
    public static function driverName() {
                                            	
    }
    /**
     * parseDSN
     *
     * @param 	string	$dsn
     * @return 	array 	Parsed contents of DSN
     */
    function parseDSN($dsn) {
		$parts = @parse_url($dsn);
                             
        $names = array('scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment');
        
        foreach($names as $name) {
            if( ! isset($parts[$name]))
                $parts[$name] = null;
        }

		if(count($parts) == 0 || ! isset($parts['scheme']))
		  throw new Doctrine_DB_Exception('Empty data source name');

        $drivers = self::getAvailableDrivers();
        
        if(isset(self::$driverMap[$parts['scheme']]))
            $parts['scheme'] = self::$driverMap[$parts['scheme']];

        if( ! in_array($parts['scheme'], $drivers))
            throw new Doctrine_DB_Exception('Driver '.$parts['scheme'].' not availible or extension not loaded');

        switch($parts['scheme']) {
            case 'sqlite':
                if(isset($parts['host']) && $parts['host'] == ':memory') {
                    $parts['database'] = ':memory:';
                    $parts['dsn']      = 'sqlite::memory:';
                }

            break;
            case 'mysql':
            case 'informix':
            case 'oci8':
            case 'mssql':
            case 'firebird':
            case 'pgsql':
            case 'odbc':
                if( ! isset($parts['path']) || $parts['path'] == '/')
                    throw new Doctrine_DB_Exception('No database availible in data source name');

         		if(isset($parts['path']))
                    $parts['database'] = substr($parts['path'], 1);
                
                if( ! isset($parts['host'])) 
                    throw new Doctrine_DB_Exception('No hostname set in data source name');

                $parts['dsn'] = $parts["scheme"].":host=".$parts["host"].";dbname=".$parts["database"];
            break;
            default: 
                throw new Doctrine_DB_Exception('Unknown driver '.$parts['scheme']);
        } 

		return $parts;
	}
    /**
     * clear
     * clears all instances from the memory
     *
     * @return void
     */
    public static function clear() {
        self::$instances = array();
    }

    /**
     * errorCode
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode() {
        return $this->dbh->errorCode();
    }
    /**
     * errorInfo
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo() {
        return $this->dbh->errorInfo();
    }
    /**
     * prepare
     *
     * @param string $statement
     */
    public function prepare($statement) {
        $args = func_get_args();

        $this->listener->onPrePrepare($this, $args);

        $stmt = $this->dbh->prepare($statement);

        $this->listener->onPrepare($this, $args);
        
        return $stmt;
    }
    /**
     * query
     *
     * @param string $statement
     * @return Doctrine_DB_Statement|boolean
     */
    public function query($statement, $fetchMode = null, $arg = null, $arg2 = null) {
        $args = func_get_args();

        $this->listener->onPreQuery($this, $args);

        $stmt = $this->dbh->query($statement, $fetchMode, $arg, $arg2);

        $this->listener->onQuery($this, $args);

        return $stmt;
    }
    /**
     * quote
     * quotes a string for use in a query
     *
     * @param string $input
     * @return string
     */
    public function quote($input) {
        $this->connect();

        return $this->dbh->quote($input);
    }
    /**
     * exec
     * executes an SQL statement and returns the number of affected rows
     *
     * @param string $statement
     * @return integer
     */
    public function exec($statement) {
        $args = func_get_args();

        $this->listener->onPreExec($this, $args);

        $rows = $this->dbh->exec($statement);

        $this->listener->onExec($this, $args);

        return $rows;
    }
    /**
     * lastInsertId
     *
     *
     */
    public function lastInsertId() {
        $this->connect();

        return $this->dbh->lastInsertId();
    }
    /**
     * begins a transaction
     *
     * @return boolean
     */
    public function beginTransaction() {
        $this->listener->onPreBeginTransaction($this);

        $return = $this->dbh->beginTransaction();

        $this->listener->onBeginTransaction($this);
    
        return $return;
    }
    /**
     * commits a transaction
     *
     * @return boolean
     */
    public function commit() {
        $this->listener->onPreCommit($this);

        $return = $this->dbh->commit();

        $this->listener->onCommit($this);

        return $return;
    }
    /**
     * rollBack
     *
     * @return boolean
     */
    public function rollBack() {
        $this->connect();
        
        $this->dbh->rollBack();
    }
    /**
     * getAttribute
     * retrieves a database connection attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        $this->connect();
        
        $this->dbh->getAttribute($attribute);
    }
    /**
     * returns an array of available PDO drivers
     */
    public static function getAvailableDrivers() {
        return PDO::getAvailableDrivers();
    }
    /**
     * setAttribute
     * sets an attribute
     *
     * @param integer $attribute
     * @param mixed $value
     * @return boolean
     */
    public function setAttribute($attribute, $value) {
        $this->connect();
        
        $this->dbh->setAttribute($attribute, $value);
    }
    /**
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->queries);
    }
    /**
     * count
     * returns the number of executed queries
     *
     * @return integer
     */
    public function count() {
        return count($this->queries);
    }

}

