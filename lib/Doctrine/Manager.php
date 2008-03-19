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
 * <http://www.phpdoctrine.org>.
 */

/**
 *
 * Doctrine_Manager is the base component of all doctrine based projects.
 * It opens and keeps track of all connections (database connections).
 *
 * @package     Doctrine
 * @subpackage  Manager
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Manager extends Doctrine_Configurable implements Countable, IteratorAggregate
{
    /**
     * @var array $connections          an array containing all the opened connections
     */
    protected $_connections   = array();

    /**
     * @var array $bound                an array containing all components that have a bound connection
     */
    protected $_bound         = array();

    /**
     * @var integer $index              the incremented index
     */
    protected $_index         = 0;

    /**
     * @var integer $currIndex          the current connection index
     */
    protected $_currIndex     = 0;

    /**
     * @var string $root                root directory
     */
    protected $_root;

    /**
     * @var Doctrine_Query_Registry     the query registry
     */
    protected $_queryRegistry;

    /**
     *
     */
    protected static $driverMap = array('oci' => 'oracle');

    /**
     * constructor
     *
     * this is private constructor (use getInstance to get an instance of this class)
     */
    private function __construct()
    {
        $this->_root = dirname(__FILE__);
    }

    /**
     * setDefaultAttributes
     * sets default attributes
     *
     * @todo I do not understand the flow here. Explain or refactor?
     * @return boolean
     */
    public function setDefaultAttributes()
    {
        static $init = false;
        if ( ! $init) {
            $init = true;
            $attributes = array(
                        Doctrine::ATTR_CACHE                    => null,
                        Doctrine::ATTR_RESULT_CACHE             => null,
                        Doctrine::ATTR_QUERY_CACHE              => null,
                        Doctrine::ATTR_LOAD_REFERENCES          => true,
                        Doctrine::ATTR_LISTENER                 => new Doctrine_EventListener(),
                        Doctrine::ATTR_RECORD_LISTENER          => null,
                        Doctrine::ATTR_THROW_EXCEPTIONS         => true,
                        Doctrine::ATTR_VALIDATE                 => Doctrine::VALIDATE_NONE,
                        Doctrine::ATTR_QUERY_LIMIT              => Doctrine::LIMIT_RECORDS,
                        Doctrine::ATTR_IDXNAME_FORMAT           => "%s_idx",
                        Doctrine::ATTR_SEQNAME_FORMAT           => "%s_seq",
                        Doctrine::ATTR_TBLNAME_FORMAT           => "%s",
                        Doctrine::ATTR_QUOTE_IDENTIFIER         => false,
                        Doctrine::ATTR_SEQCOL_NAME              => 'id',
                        Doctrine::ATTR_PORTABILITY              => Doctrine::PORTABILITY_ALL,
                        Doctrine::ATTR_EXPORT                   => Doctrine::EXPORT_ALL,
                        Doctrine::ATTR_DECIMAL_PLACES           => 2,
                        Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE  => 'doctrine'
                        );
            foreach ($attributes as $attribute => $value) {
                $old = $this->getAttribute($attribute);
                if ($old === null) {
                    $this->setAttribute($attribute,$value);
                }
            }
            return true;
        }
        return false;
    }
    
    public function hasAttribute($key)
    {
        switch ($key) {
            case Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE:
            case Doctrine::ATTR_COLL_KEY:
            case Doctrine::ATTR_SEQCOL_NAME:
            case Doctrine::ATTR_LISTENER:
            case Doctrine::ATTR_RECORD_LISTENER:
            case Doctrine::ATTR_QUOTE_IDENTIFIER:
            case Doctrine::ATTR_FIELD_CASE:
            case Doctrine::ATTR_IDXNAME_FORMAT:
            case Doctrine::ATTR_SEQNAME_FORMAT:
            case Doctrine::ATTR_DBNAME_FORMAT:
            case Doctrine::ATTR_TBLCLASS_FORMAT:
            case Doctrine::ATTR_TBLNAME_FORMAT:
            case Doctrine::ATTR_EXPORT:
            case Doctrine::ATTR_DECIMAL_PLACES:
            case Doctrine::ATTR_PORTABILITY:
            case Doctrine::ATTR_VALIDATE:
            case Doctrine::ATTR_QUERY_LIMIT:
            case Doctrine::ATTR_DEFAULT_TABLE_TYPE:
            case Doctrine::ATTR_DEF_TEXT_LENGTH:
            case Doctrine::ATTR_DEF_VARCHAR_LENGTH:
            case Doctrine::ATTR_DEF_TABLESPACE:
            case Doctrine::ATTR_EMULATE_DATABASE:
            case Doctrine::ATTR_USE_NATIVE_ENUM:
            case Doctrine::ATTR_CREATE_TABLES:
            case Doctrine::ATTR_COLL_LIMIT:
            case Doctrine::ATTR_CACHE: // deprecated
            case Doctrine::ATTR_RESULT_CACHE:
            case Doctrine::ATTR_CACHE_LIFESPAN: // deprecated
            case Doctrine::ATTR_RESULT_CACHE_LIFESPAN:
            case Doctrine::ATTR_LOAD_REFERENCES:
            case Doctrine::ATTR_THROW_EXCEPTIONS:
            case Doctrine::ATTR_QUERY_CACHE:
            case Doctrine::ATTR_QUERY_CACHE_LIFESPAN:
            case Doctrine::ATTR_MODEL_LOADING:
            case Doctrine::ATTR_METADATA_CACHE:
            case Doctrine::ATTR_METADATA_CACHE_LIFESPAN:
                return true;
            default:
                return false;
        }
    }

    /**
     * returns the root directory of Doctrine
     *
     * @return string
     * @todo Better name.
     */
    final public function getRoot()
    {
        return $this->_root;
    }

    /**
     * getInstance
     * returns an instance of this class
     * (this class uses the singleton pattern)
     *
     * @return Doctrine_Manager
     */
    public static function getInstance()
    {
        static $instance;
        if ( ! isset($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * getQueryRegistry
     * lazy-initializes the query registry object and returns it
     *
     * @return Doctrine_Query_Registry
     */
    public function getQueryRegistry()
    {
    	if ( ! isset($this->_queryRegistry)) {
    	   $this->_queryRegistry = new Doctrine_Query_Registry;
    	}
        return $this->_queryRegistry;
    }

    /**
     * setQueryRegistry
     * sets the query registry
     *
     * @return Doctrine_Manager     this object
     */
    public function setQueryRegistry(Doctrine_Query_Registry $registry)
    {
        $this->_queryRegistry = $registry;

        return $this;
    }

    /**
     * fetch
     * fetches data using the provided queryKey and
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function find($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                ->getQueryRegistry()
                ->get($queryKey)
                ->execute($params, $hydrationMode);
    }

    /**
     * fetchOne
     * fetches data using the provided queryKey and
     * the associated query in the query registry
     *
     * if no query for given queryKey is being found a
     * Doctrine_Query_Registry exception is being thrown
     *
     * @param string $queryKey      the query key
     * @param array $params         prepared statement params (if any)
     * @return mixed                the fetched data
     */
    public function findOne($queryKey, $params = array(), $hydrationMode = Doctrine::HYDRATE_RECORD)
    {
        return Doctrine_Manager::getInstance()
                ->getQueryRegistry()
                ->get($queryKey)
                ->fetchOne($params, $hydrationMode);
    }

    /**
     * connection
     *
     * if the adapter parameter is set this method acts as
     * a short cut for Doctrine_Manager::getInstance()->openConnection($adapter, $name);
     *
     * if the adapter paramater is not set this method acts as
     * a short cut for Doctrine_Manager::getInstance()->getCurrentConnection()
     *
     * @param PDO|Doctrine_Adapter_Interface $adapter   database driver
     * @param string $name                              name of the connection, if empty numeric key is used
     * @throws Doctrine_Manager_Exception               if trying to bind a connection with an existing name
     * @return Doctrine_Connection
     */
    public static function connection($adapter = null, $name = null)
    {
        if ($adapter == null) {
            return Doctrine_Manager::getInstance()->getCurrentConnection();
        } else {
            return Doctrine_Manager::getInstance()->openConnection($adapter, $name);
        }
    }

    /**
     * openConnection
     * opens a new connection and saves it to Doctrine_Manager->connections
     *
     * @param PDO|Doctrine_Adapter_Interface $adapter   database driver
     * @param string $name                              name of the connection, if empty numeric key is used
     * @throws Doctrine_Manager_Exception               if trying to bind a connection with an existing name
     * @throws Doctrine_Manager_Exception               if trying to open connection for unknown driver
     * @return Doctrine_Connection
     */
    public function openConnection($adapter, $name = null, $setCurrent = true)
    {
        if (is_object($adapter)) {
            if ( ! ($adapter instanceof PDO) && ! in_array('Doctrine_Adapter_Interface', class_implements($adapter))) {
                throw new Doctrine_Manager_Exception("First argument should be an instance of PDO or implement Doctrine_Adapter_Interface");
            }

            $driverName = $adapter->getAttribute(Doctrine::ATTR_DRIVER_NAME);
        } else if (is_array($adapter)) {
            if ( ! isset($adapter[0])) {
                throw new Doctrine_Manager_Exception('Empty data source name given.');
            }
            $e = explode(':', $adapter[0]);

            if ($e[0] == 'uri') {
                $e[0] = 'odbc';
            }

            $parts['dsn']    = $adapter[0];
            $parts['scheme'] = $e[0];
            $parts['user']   = (isset($adapter[1])) ? $adapter[1] : null;
            $parts['pass']   = (isset($adapter[2])) ? $adapter[2] : null;

            $driverName = $e[0];
            $adapter = $parts;
        } else {
            $parts = $this->parseDsn($adapter);
            $driverName = $parts['scheme'];
            $adapter = $parts;
        }

        // initialize the default attributes
        $this->setDefaultAttributes();

        if ($name !== null) {
            $name = (string) $name;
            if (isset($this->_connections[$name])) {
                if ($setCurrent) {
                    $this->_currIndex = $name;
                }
                return $this->_connections[$name];
            }
        } else {
            $name = $this->_index;
            $this->_index++;
        }

        $drivers = array('mysql'    => 'Doctrine_Connection_Mysql',
                         'sqlite'   => 'Doctrine_Connection_Sqlite',
                         'pgsql'    => 'Doctrine_Connection_Pgsql',
                         'oci'      => 'Doctrine_Connection_Oracle',
                         'oci8'     => 'Doctrine_Connection_Oracle',
                         'oracle'   => 'Doctrine_Connection_Oracle',
                         'mssql'    => 'Doctrine_Connection_Mssql',
                         'dblib'    => 'Doctrine_Connection_Mssql',
                         'firebird' => 'Doctrine_Connection_Firebird',
                         'informix' => 'Doctrine_Connection_Informix',
                         'mock'     => 'Doctrine_Connection_Mock');

        if ( ! isset($drivers[$driverName])) {
            throw new Doctrine_Manager_Exception('Unknown driver ' . $driverName);
        }

        $className = $drivers[$driverName];
        $conn = new $className($this, $adapter);
        $conn->setName($name);

        $this->_connections[$name] = $conn;

        if ($setCurrent) {
            $this->_currIndex = $name;
        }
        return $this->_connections[$name];
    }

    /**
     * parsePdoDsn
     *
     * @param array $dsn An array of dsn information
     * @return array The array parsed
     * @todo package:dbal
     */
    public function parsePdoDsn($dsn)
    {
        $parts = array();

        $names = array('dsn', 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment');

        foreach ($names as $name) {
            if ( ! isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        $e = explode(':', $dsn);
        $parts['scheme'] = $e[0];
        $parts['dsn'] = $dsn;

        $e = explode(';', $e[1]);
        foreach ($e as $string) {
            if ($string) {
                $e2 = explode('=', $string);

                if (isset($e2[0]) && isset($e2[1])) {
                    list($key, $value) = $e2;
                    $parts[$key] = $value;
                }
            }
        }

        return $parts;
    }

    /**
     * parseDsn
     *
     * @param string $dsn
     * @return array Parsed contents of DSN
     * @todo package:dbal
     */
    public function parseDsn($dsn)
    {
        // fix sqlite dsn so that it will parse correctly
        $dsn = str_replace("////", "/", $dsn);
        $dsn = str_replace("///c:/", "//c:/", $dsn);

        // silence any warnings
        $parts = @parse_url($dsn);

        $names = array('dsn', 'scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment');

        foreach ($names as $name) {
            if ( ! isset($parts[$name])) {
                $parts[$name] = null;
            }
        }

        if (count($parts) == 0 || ! isset($parts['scheme'])) {
            throw new Doctrine_Manager_Exception('Empty data source name');
        }

        switch ($parts['scheme']) {
            case 'sqlite':
            case 'sqlite2':
            case 'sqlite3':
                if (isset($parts['host']) && $parts['host'] == ':memory') {
                    $parts['database'] = ':memory:';
                    $parts['dsn']      = 'sqlite::memory:';
                } else {
                    //fix windows dsn we have to add host: to path and set host to null
                    if (isset($parts['host'])) {
                        $parts['path'] = $parts['host'] . ":" . $parts["path"];
                        $parts["host"] = null;
                    }
                    $parts['database'] = $parts['path'];
                    $parts['dsn'] = $parts['scheme'] . ':' . $parts['path'];
                }

                break;

            case 'mssql':
            case 'dblib':
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Manager_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new Doctrine_Manager_Exception('No hostname set in data source name');
                }

                if (isset(self::$driverMap[$parts['scheme']])) {
                    $parts['scheme'] = self::$driverMap[$parts['scheme']];
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                              . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port']:null) . ';dbname='
                              . $parts['database'];

                break;

            case 'mysql':
            case 'informix':
            case 'oci8':
            case 'oci':
            case 'firebird':
            case 'pgsql':
            case 'odbc':
            case 'mock':
            case 'oracle':
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Manager_Exception('No database available in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new Doctrine_Manager_Exception('No hostname set in data source name');
                }

                if (isset(self::$driverMap[$parts['scheme']])) {
                    $parts['scheme'] = self::$driverMap[$parts['scheme']];
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                              . $parts['host'] . (isset($parts['port']) ? ';port=' . $parts['port']:null) . ';dbname='
                              . $parts['database'];

                break;
            default:
                throw new Doctrine_Manager_Exception('Unknown driver '.$parts['scheme']);
        }

        return $parts;
    }

    /**
     * getConnection
     * @param integer $index
     * @return object Doctrine_Connection
     * @throws Doctrine_Manager_Exception   if trying to get a non-existent connection
     */
    public function getConnection($name)
    {
        if ( ! isset($this->_connections[$name])) {
            throw new Doctrine_Manager_Exception('Unknown connection: ' . $name);
        }

        return $this->_connections[$name];
    }
    
    /**
     * Creates a new Doctrine_Query object that uses the currently active connection.
     * 
     * @return Doctrine_Query 
     */
    public function createQuery($dql = "")
    {
        $query = new Doctrine_Query($this->getCurrentConnection());
        if ( ! empty($dql)) {
            $query->parseQuery($dql);
        }
        
        return $query;
    }
    
    /**
     * Creates a new native (SQL) query.
     *
     * @return Doctrine_RawSql
     */
    public function createNativeQuery($sql = "")
    {
        $nativeQuery = new Doctrine_RawSql($this->getCurrentConnection());
        if ( ! empty($sql)) {
            $nativeQuery->parseQuery($sql);
        }
        
        return $nativeQuery;
    }
    
    /**
     * Creates a query object out of a registered, named query.
     *
     * @param string $name     The name of the query.
     * @return Doctrine_Query  The query object.
     */
    public function createNamedQuery($name)
    {
        return $this->_queryRegistry->get($name);
    }

    /**
     * getComponentAlias
     * retrieves the alias for given component name
     * if the alias couldn't be found, this method returns the given
     * component name
     *
     * @param string $componentName
     * @return string                   the component alias
     */
    public function getComponentAlias($componentName)
    {
        if (isset($this->componentAliases[$componentName])) {
            return $this->componentAliases[$componentName];
        }

        return $componentName;
    }

    /**
     * sets an alias for given component name
     * very useful when building a large framework with a possibility
     * to override any given class
     *
     * @param string $componentName         the name of the component
     * @param string $alias
     * @return Doctrine_Manager
     */
    public function setComponentAlias($componentName, $alias)
    {
        $this->componentAliases[$componentName] = $alias;

        return $this;
    }

    /**
     * getConnectionName
     *
     * @param Doctrine_Connection $conn     connection object to be searched for
     * @return string                       the name of the connection
     */
    public function getConnectionName(Doctrine_Connection $conn)
    {
        return array_search($conn, $this->_connections, true);
    }

    /**
     * bindComponent
     * binds given component to given connection
     * this means that when ever the given component uses a connection
     * it will be using the bound connection instead of the current connection
     *
     * @param string $componentName
     * @param string $connectionName
     * @return boolean
     */
    public function bindComponent($componentName, $connectionName)
    {
        $this->_bound[$componentName] = $connectionName;
    }

    /**
     * getConnectionForComponent
     *
     * @param string $componentName
     * @return Doctrine_Connection
     */
    public function getConnectionForComponent($componentName = null)
    {
        if (isset($this->_bound[$componentName])) {
            return $this->getConnection($this->_bound[$componentName]);
        }
        return $this->getCurrentConnection();
    }

    /**
     * hasConnectionForComponent
     *
     * @param string $componentName
     * @return boolean
     */
    public function hasConnectionForComponent($componentName = null)
    {
        return isset($this->_bound[$componentName]);
    }

    /**
     * getTable
     * this is the same as Doctrine_Connection::getTable() except
     * that it works seamlessly in multi-server/connection environment
     *
     * @see Doctrine_Connection::getTable()
     * @param string $componentName
     * @return Doctrine_Table
     * @deprecated
     */
    public function getTable($componentName)
    {
        return $this->getConnectionForComponent($componentName)->getTable($componentName);
    }

    /**
     * getMapper
     * Returns the mapper object for the given component name.
     *
     * @param string $componentName
     * @return Doctrine_Mapper
     */
    public function getMapper($componentName)
    {
        return $this->getConnectionForComponent($componentName)->getMapper($componentName);
    }

    /**
     * table
     * this is the same as Doctrine_Connection::getTable() except
     * that it works seamlessly in multi-server/connection environment
     *
     * @see Doctrine_Connection::getTable()
     * @param string $componentName
     * @return Doctrine_Table
     */
    public static function table($componentName)
    {
        return Doctrine_Manager::getInstance()
               ->getConnectionForComponent($componentName)
               ->getTable($componentName);
    }

    /**
     * closes the connection
     *
     * @param Doctrine_Connection $connection
     * @return void
     */
    public function closeConnection(Doctrine_Connection $connection)
    {
        $connection->close();

        $key = array_search($connection, $this->_connections, true);

        if ($key !== false) {
            unset($this->_connections[$key]);
        }
        $this->_currIndex = key($this->_connections);

        unset($connection);
    }

    /**
     * getConnections
     * returns all opened connections
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->_connections;
    }

    /**
     * setCurrentConnection
     * sets the current connection to $key
     *
     * @param mixed $key                        the connection key
     * @throws InvalidKeyException
     * @return void
     */
    public function setCurrentConnection($key)
    {
        $key = (string) $key;
        if ( ! isset($this->_connections[$key])) {
            throw new InvalidKeyException();
        }
        $this->_currIndex = $key;
    }

    /**
     * contains
     * whether or not the manager contains specified connection
     *
     * @param mixed $key                        the connection key
     * @return boolean
     */
    public function contains($key)
    {
        return isset($this->_connections[$key]);
    }

    /**
     * count
     * returns the number of opened connections
     *
     * @return integer
     * @todo This is unintuitive.
     */
    public function count()
    {
        return count($this->_connections);
    }

    /**
     * getIterator
     * returns an ArrayIterator that iterates through all connections
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_connections);
    }

    /**
     * getCurrentConnection
     * returns the current connection
     *
     * @throws Doctrine_Connection_Exception       if there are no open connections
     * @return Doctrine_Connection
     */
    public function getCurrentConnection()
    {
        $i = $this->_currIndex;
        if ( ! isset($this->_connections[$i])) {
            throw new Doctrine_Connection_Exception();
        }
        return $this->_connections[$i];
    }

    /**
     * createDatabases
     *
     * Creates databases for connections
     *
     * @param string $specifiedConnections Array of connections you wish to create the database for
     * @return void
     * @todo package:dbal
     */
    public function createDatabases($specifiedConnections = array())
    {
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $results = array();

        foreach ($this as $name => $connection) {
            if ( ! empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            $results[$name] = $connection->createDatabase();
        }

        return $results;
    }

    /**
     * dropDatabases
     *
     * Drops databases for connections
     *
     * @param string $specifiedConnections Array of connections you wish to drop the database for
     * @return void
     * @todo package:dbal
     */
    public function dropDatabases($specifiedConnections = array())
    {
        if ( ! is_array($specifiedConnections)) {
            $specifiedConnections = (array) $specifiedConnections;
        }

        $results = array();

        foreach ($this as $name => $connection) {
            if ( ! empty($specifiedConnections) && !in_array($name, $specifiedConnections)) {
                continue;
            }

            $results[$name] = $connection->dropDatabase();
        }

        return $results;
    }

    /**
     * __toString
     * returns a string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        $r[] = "<pre>";
        $r[] = "Doctrine_Manager";
        $r[] = "Connections : ".count($this->_connections);
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
