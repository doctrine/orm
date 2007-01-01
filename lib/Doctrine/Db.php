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
 * Doctrine_Db
 * A thin wrapper layer on top of PDO / Doctrine_Adapter
 *
 * Doctrine_Db provides the following things to underlying database hanlder
 *
 * 1. Event listeners
 *    An easy to use, pluggable eventlistener architecture. Aspects such as
 *    logging, query profiling and caching can be easily implemented through
 *    the use of these listeners
 *
 * 2. Lazy-connecting
 *    Creating an instance of Doctrine_Db does not connect
 *    to database. Connecting to database is only invoked when actually needed
 *    (for example when query() is being called)
 *
 * 3. Portable error codes
 *    Doctrine_Db_Exception drivers provide portable error code handling.
 *
 * 4. Easy-to-use fetching methods
 *    For convience Doctrine_Db provides methods such as fetchOne(), fetchAssoc() etc.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db implements Countable, IteratorAggregate, Doctrine_Adapter_Interface
{
    /**
     * @var array $instances        all the instances of this class
     */
    protected static $instances   = array();
    /**
     * @var array $isConnected      whether or not a connection has been established
     */
    protected $isConnected        = false;
    /**
     * @var PDO $dbh                the database handler
     */
    protected $dbh;
    /**
     * @var array $options
     */
    protected $options            = array('dsn'      => null,
                                          'username' => null,
                                          'password' => null,
                                          );
    /**
     * @var Doctrine_Db_EventListener_Interface|Doctrine_Overloadable $listener
     *                              listener for listening events
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
     * @param string $user          database username
     * @param string $pass          database password
     */
    public function __construct($dsn, $user = null, $pass = null)
    {
        if ( ! isset($user)) {
            $a = self::parseDSN($dsn);

            extract($a);
        }
        $this->options['dsn']      = $dsn;
        $this->options['username'] = $user;
        $this->options['password'] = $pass;
        $this->listener = new Doctrine_Db_EventListener();
    }

    public function nextQuerySequence()
    {
        return ++$this->querySequence;
    }
    /**
     * getQuerySequence
     */
    public function getQuerySequence()
    {
        return $this->querySequence;
    }
    /**
     * getDBH
     */
    public function getDBH()
    {
        return $this->dbh;
    }
    public function getOption($name)
    {
        if ( ! array_key_exists($name, $this->options)) {
            throw new Doctrine_Db_Exception('Unknown option ' . $name);
        }
        return $this->options[$name];
    }
    /**
     * addListener
     *
     * @param Doctrine_Db_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Db
     */
    public function addListener($listener, $name = null)
    {
        if ( ! ($this->listener instanceof Doctrine_Db_EventListener_Chain)) {
            $this->listener = new Doctrine_Db_EventListener_Chain();
        }
        $this->listener->add($listener, $name);

        return $this;
    }
    /**
     * getListener
     *
     * @return Doctrine_Db_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener()
    {
        return $this->listener;
    }
    /**
     * setListener
     *
     * @param Doctrine_Db_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Db
     */
    public function setListener($listener)
    {
        if ( ! ($listener instanceof Doctrine_Db_EventListener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Db_Exception("Couldn't set eventlistener for database handler. EventListeners should implement either Doctrine_Db_EventListener_Interface or Doctrine_Overloadable");
        }
        $this->listener = $listener;

        return $this;
    }

    /**
     * connect
     * connects into database
     *
     * @return boolean
     */
    public function connect()
    {
        if ($this->isConnected)
            return false;

        $this->dbh = new PDO($this->options['dsn'], $this->options['username'], $this->options['password']);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("Doctrine_Db_Statement", array($this)));
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
    public static function getConnection($dsn = null, $username = null, $password = null)
    {
        return new self($dsn, $username, $password);
    }
    /**
     * driverName
     * converts a driver name like (oracle) to appropriate PDO
     * driver name (oci8 in the case of oracle)
     *
     * @param string $name
     * @return string
     */
    public static function driverName($name)
    {
        if (isset(self::$driverMap[$name])) {
            return self::$driverMap[$name];
        }
        return $name;
    }
    /**
     * parseDSN
     *
     * @param string $dsn
     * @return array Parsed contents of DSN
     */
    function parseDSN($dsn)
    {
        // silence any warnings
        $parts = @parse_url($dsn);

        $names = array('scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment');

        foreach ($names as $name) {
            if ( ! isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        if (count($parts) == 0 || ! isset($parts['scheme'])) {
            throw new Doctrine_Db_Exception('Empty data source name');
        }
        $drivers = self::getAvailableDrivers();

        $parts['scheme'] = self::driverName($parts['scheme']);

        if ( ! in_array($parts['scheme'], $drivers)) {
            throw new Doctrine_Db_Exception('Driver '.$parts['scheme'].' not availible or extension not loaded');
        }
        switch ($parts['scheme']) {
            case 'sqlite':
                if (isset($parts['host']) && $parts['host'] == ':memory') {
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
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Db_Exception('No database availible in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new Doctrine_Db_Exception('No hostname set in data source name');
                }
                $parts['dsn'] = $parts["scheme"].":host=".$parts["host"].";dbname=".$parts["database"];
                break;
            default:
                throw new Doctrine_Db_Exception('Unknown driver '.$parts['scheme']);
        }

        return $parts;
    }
    /**
     * clear
     * clears all instances from the memory
     *
     * @return void
     */
    public static function clear()
    {
        self::$instances = array();
    }

    /**
     * errorCode
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode()
    {
        return $this->dbh->errorCode();
    }
    /**
     * errorInfo
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->dbh->errorInfo();
    }
    /**
     * prepare
     *
     * @param string $statement
     */
    public function prepare($statement)
    {
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
     * @return Doctrine_Db_Statement|boolean
     */
    public function query($statement, array $params = array()) {
        $this->connect();

        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::QUERY, $statement);

        $this->listener->onPreQuery($event);

        if ( ! empty($params)) {
            $stmt = $this->dbh->query($statement)->execute($params);
        } else {
            $stmt = $this->dbh->query($statement);
        }
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
    public function quote($input)
    {
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
    public function exec($statement)
    {
        $this->connect();

        $args = func_get_args();

        $event = new Doctrine_Db_Event($this, Doctrine_Db_Event::EXEC, $statement);

        $this->listener->onPreExec($event);

        $rows = $this->dbh->exec($statement);

        $this->listener->onExec($event);

        return $rows;
    }

    /**
     * lastInsertId
     *
     * @return integer
     */
    public function lastInsertId()
    {
        $this->connect();

        return $this->dbh->lastInsertId();
    }
    /**
     * begins a transaction
     *
     * @return boolean
     */
    public function beginTransaction()
    {
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
    public function commit()
    {
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
    public function rollBack()
    {
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
    public function getAttribute($attribute)
    {
        $this->connect();

        return $this->dbh->getAttribute($attribute);
    }
    /**
     * returns an array of available PDO drivers
     */
    public static function getAvailableDrivers()
    {
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
    public function setAttribute($attribute, $value)
    {
        $this->connect();

        $this->dbh->setAttribute($attribute, $value);
    }
    /**
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        if ($this->listener instanceof Doctrine_Db_Profiler)
            return $this->listener;
    }
    /**
     * count
     * returns the number of executed queries
     *
     * @return integer
     */
    public function count()
    {
        return $this->querySequence;
    }
}
