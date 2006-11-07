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
     * A connection operation or selecting a database.
     */
    const CONNECT = 1;

    /**
     * Any general database query that does not fit into the other constants.
     */
    const QUERY   = 2;

    /**
     * Adding new data to the database, such as SQL's INSERT.
     */
    const INSERT  = 4;

    /**
     * Updating existing information in the database, such as SQL's UPDATE.
     *
     */
    const UPDATE = 8;

    /**
     * An operation related to deleting data in the database,
     * such as SQL's DELETE.
     */
    const DELETE = 16;

    /**
     * Retrieving information from the database, such as SQL's SELECT.
     */
    const SELECT = 32;

    /**
     * Transactional operation, such as start transaction, commit, or rollback.
     */
    const TRANSACTION = 64;
    /**
     * default DSN
     */
    const DSN = "mysql://root:dc34@localhost/test";
    /**
     * @var array $instances        all the instances of this class
     */
    protected static $instances   = array();
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
    /**
     * @var integer $querySequence
     */
    protected $querySequence  = 0;

    private static $driverMap = array('oracle'     => 'oci8',
                                      'postgres'   => 'pgsql',
                                      'oci'        => 'oci8',
                                      'sqlite2'    => 'sqlite',
                                      'sqlite3'    => 'sqlite');


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


    public function nextQuerySequence() {
        return ++$this->querySequence;
    }
    /**
     * getQuerySequence
     */
    public function getQuerySequence() {
        return $this->querySequence;
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
     *
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }
    /**
     * getPassword
     *
     * @return string
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
        $this->isConnected = true;
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
    /** 
     * driverName
     * converts a driver name like (oracle) to appropriate PDO 
     * driver name (oci8 in the case of oracle)
     *
     * @param string $name
     * @return string
     */
    public static function driverName($name) {
        if(isset(self::$driverMap[$name]))
            return self::$driverMap[$name];

        return $name;
    }
    /**
     * parseDSN
     *
     * @param 	string	$dsn
     * @return 	array 	Parsed contents of DSN
     */
    function parseDSN($dsn) {
        // silence any warnings
		$parts = @parse_url($dsn);
                             
        $names = array('scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment');
        
        foreach($names as $name) {
            if( ! isset($parts[$name]))
                $parts[$name] = null;
        }

		if(count($parts) == 0 || ! isset($parts['scheme']))
		  throw new Doctrine_DB_Exception('Empty data source name');

        $drivers = self::getAvailableDrivers();

        $parts['scheme'] = self::driverName($parts['scheme']);

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
        $this->connect();

        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::PREPARE, $statement);

        $this->listener->onPrePrepare($event);

        $stmt = $this->dbh->prepare($statement);

        $this->listener->onPrepare($event);

        $this->querySequence++;

        return $stmt;
    }
    /**
     * query
     *
     * @param string $statement
     * @param array $params
     * @return Doctrine_DB_Statement|boolean
     */
    public function query($statement, array $params = array()) {
        $this->connect();
        
        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::QUERY, $statement);

        $this->listener->onPreQuery($event);

        if( ! empty($params))
            $stmt = $this->dbh->query($statement)->execute($params);
        else
            $stmt = $this->dbh->query($statement);

        $this->listener->onQuery($event);

        $this->querySequence++;

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
        $this->connect();

        $args = func_get_args();
        
        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::EXEC, $statement);

        $this->listener->onPreExec($event);

        $rows = $this->dbh->exec($statement);

        $this->listener->onExec($event);

        return $rows;
    }
    /**
     * fetchAll
     *
     * @return array
     */
    public function fetchAll($statement, array $params = array()) {
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne($statement, array $params = array()) {
        return current($this->query($statement, $params)->fetch(PDO::FETCH_NUM));
    }
    
    public function fetchRow($statement, array $params = array()) {
        return $this->query($statement, $params)->fetch(PDO::FETCH_ASSOC);
    }
    
    public function fetchArray($statement, array $params = array()) {
        return $this->query($statement, $params)->fetch(PDO::FETCH_NUM);
    }
    public function fetchColumn($statement, array $params = array()) {
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_COLUMN);
    }
    public function fetchAssoc($statement, array $params = array()) {
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function fetchBoth($statement, array $params = array()) { 
        return $this->query($statement, $params)->fetchAll(PDO::FETCH_BOTH);
    }
    /**
     * lastInsertId
     *
     * @return integer
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
        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::BEGIN);

        $this->listener->onPreBeginTransaction($event);

        $return = $this->dbh->beginTransaction();

        $this->listener->onBeginTransaction($event);
    
        return $return;
    }
    /**
     * commits a transaction
     *
     * @return boolean
     */
    public function commit() {
        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::COMMIT);

        $this->listener->onPreCommit($event);

        $return = $this->dbh->commit();

        $this->listener->onCommit($event);

        return $return;
    }
    /**
     * rollBack
     *
     * @return boolean
     */
    public function rollBack() {
        $this->connect();

        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::ROLLBACK);

        $this->listener->onPreRollback($event);

        $this->dbh->rollBack();
        
        $this->listener->onRollback($event);
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
        if($this->listener instanceof Doctrine_DB_Profiler)
            return $this->listener;
    }
    /**
     * count
     * returns the number of executed queries
     *
     * @return integer
     */
    public function count() {
        return $this->querySequence;
    }

}

