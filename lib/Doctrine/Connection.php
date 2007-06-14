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
Doctrine::autoload('Doctrine_Configurable');
/**
 * Doctrine_Connection
 *
 * A wrapper layer on top of PDO / Doctrine_Adapter
 *
 * Doctrine_Connection is the heart of any Doctrine based application.
 *
 * 1. Event listeners
 *    An easy to use, pluggable eventlistener architecture. Aspects such as
 *    logging, query profiling and caching can be easily implemented through
 *    the use of these listeners
 *
 * 2. Lazy-connecting
 *    Creating an instance of Doctrine_Connection does not connect
 *    to database. Connecting to database is only invoked when actually needed
 *    (for example when query() is being called) 
 *
 * 3. Convenience methods
 *    Doctrine_Connection provides many convenience methods such as fetchAll(), fetchOne() etc.
 *
 * 4. Modular structure
 *    Higher level functionality such as schema importing, exporting, sequence handling etc.
 *    is divided into modules. For a full list of connection modules see 
 *    Doctrine_Connection::$_modules
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 */
abstract class Doctrine_Connection extends Doctrine_Configurable implements Countable, IteratorAggregate
{
    /**
     * @var $dbh                                the database handler
     */
    protected $dbh;
    /**
     * @var array $tables                       an array containing all the initialized Doctrine_Table objects
     *                                          keys representing Doctrine_Table component names and values as Doctrine_Table objects
     */
    protected $tables           = array();
    /**
     * @var array $exported
     */
    protected $exported         = array();
    /**
     * @var string $driverName                  the name of this connection driver
     */
    protected $driverName;
    /**
     * @var boolean $isConnected                whether or not a connection has been established
     */
    protected $isConnected      = false;
    /**
     * @var array $supported                    an array containing all features this driver supports,
     *                                          keys representing feature names and values as
     *                                          one of the following (true, false, 'emulated')
     */
    protected $supported        = array();
    /**
     * @var array $pendingAttributes            An array of pending attributes. When setting attributes
     *                                          no connection is needed. When connected all the pending
     *                                          attributes are passed to the underlying adapter (usually PDO) instance.
     */
    protected $pendingAttributes  = array();
    /**
     * @var array $modules                      an array containing all modules
     *              transaction                 Doctrine_Transaction driver, handles savepoint and transaction isolation abstraction
     *
     *              expression                  Doctrine_Expression driver, handles expression abstraction
     *
     *              dataDict                    Doctrine_DataDict driver, handles datatype abstraction
     *
     *              export                      Doctrine_Export driver, handles db structure modification abstraction (contains
     *                                          methods such as alterTable, createConstraint etc.)
     *              import                      Doctrine_Import driver, handles db schema reading
     *
     *              sequence                    Doctrine_Sequence driver, handles sequential id generation and retrieval
     *
     *              unitOfWork                  Doctrine_Connection_UnitOfWork handles many orm functionalities such as object
     *                                          deletion and saving
     *
     *              formatter                   Doctrine_Formatter handles data formatting, quoting and escaping
     *
     * @see Doctrine_Connection::__get()
     * @see Doctrine_DataDict
     * @see Doctrine_Expression
     * @see Doctrine_Export
     * @see Doctrine_Transaction
     * @see Doctrine_Sequence
     * @see Doctrine_Connection_UnitOfWork
     * @see Doctrine_Formatter
     */
    private $modules = array('transaction' => false,
                             'expression'  => false,
                             'dataDict'    => false,
                             'export'      => false,
                             'import'      => false,
                             'sequence'    => false,
                             'unitOfWork'  => false,
                             'formatter'   => false,
                             'util'        => false,
                             );
    /**
     * @var array $properties               an array of connection properties
     */
    protected $properties = array('sql_comments'        => array(array('start' => '--', 'end' => "\n", 'escape' => false),
                                                                 array('start' => '/*', 'end' => '*/', 'escape' => false)),
                                  'identifier_quoting'  => array('start' => '"', 'end' => '"','escape' => '"'),
                                  'string_quoting'      => array('start' => "'",
                                                                 'end' => "'",
                                                                 'escape' => false,
                                                                 'escape_pattern' => false),
                                  'wildcards'           => array('%', '_'),
                                  'varchar_max_length'  => 255,
                                  );
    /**
     * @var array $serverInfo
     */
    protected $serverInfo = array();
    
    protected $options    = array();
    /**
     * @var array $availableDrivers         an array containing all availible drivers
     */
    private static $availableDrivers    = array(
                                        'Mysql',
                                        'Pgsql',
                                        'Oracle',
                                        'Informix',
                                        'Mssql',
                                        'Sqlite',
                                        'Firebird'
                                        );

    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager                 the manager object
     * @param PDO|Doctrine_Adapter_Interface $adapter   database driver
     */
    public function __construct(Doctrine_Manager $manager, $adapter, $user = null, $pass = null)
    {
        if ( ! ($adapter instanceof PDO) && ! in_array('Doctrine_Adapter_Interface', class_implements($adapter))) {
            if ( ! is_string($adapter)) {
                throw new Doctrine_Connection_Exception('Data source name should be a string, ' . get_class($adapter) . ' given.');
            }

            $dsn = $adapter;

            // check if dsn is PEAR-like or not
            if ( ! isset($user) || strpos($dsn, '://')) {
                $a = self::parseDSN($dsn);

                extract($a);
            } else {
                $e = explode(':', $dsn);

                if($e[0] == 'uri') {
                    $e[0] = 'odbc';
                }
    
                $this->pendingAttributes[Doctrine::ATTR_DRIVER_NAME] = $e[0];
            }
            $this->options['dsn']      = $dsn;
            $this->options['username'] = $user;
            $this->options['password'] = $pass;
        } else {
            $this->dbh = $adapter;
            
            $this->isConnected = true;
        }

        $this->setParent($manager);

        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
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

    	if ($attribute >= 100) {
            if ( ! isset($this->attributes[$attribute])) {
                return $this->parent->getAttribute($attribute);
            }
            return $this->attributes[$attribute];
    	}

        if ($this->isConnected) {
            try {
                return $this->dbh->getAttribute($attribute);
            } catch(Exception $e) {
                throw new Doctrine_Connection_Exception('Attribute ' . $attribute . ' not found.');
            }
        } else {
            if ( ! isset($this->pendingAttributes[$attribute])) {
                $this->connect();
                $this->getAttribute($attribute);
            }

            return $this->pendingAttributes[$attribute];
        }
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
    	if ($attribute >= 100) {
            parent::setAttribute($attribute, $value);
    	} else {
            if ($this->isConnected) {
                $this->dbh->setAttribute($attribute, $value);
            } else {
                $this->pendingAttributes[$attribute] = $value;
            }
        }
        return $this;
    }
    /**
     * parseDSN
     *
     * @param string $dsn
     * @return array Parsed contents of DSN
     */
    public function parseDSN($dsn)
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
            throw new Doctrine_Connection_Exception('Empty data source name');
        }
        $drivers = self::getAvailableDrivers();

        $parts['scheme'] = self::driverName($parts['scheme']);
        /**
        if ( ! in_array($parts['scheme'], $drivers)) {
            throw new Doctrine_Db_Exception('Driver '.$parts['scheme'].' not availible or extension not loaded');
        }
        */
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
            case 'dblib':
            case 'pgsql':
            case 'odbc':
            case 'mock':
            case 'oracle':
                if ( ! isset($parts['path']) || $parts['path'] == '/') {
                    throw new Doctrine_Connection_Exception('No database availible in data source name');
                }
                if (isset($parts['path'])) {
                    $parts['database'] = substr($parts['path'], 1);
                }
                if ( ! isset($parts['host'])) {
                    throw new Doctrine_Connection_Exception('No hostname set in data source name');
                }
                
                if (isset(self::$driverMap[$parts['scheme']])) {
                    $parts['scheme'] = self::$driverMap[$parts['scheme']];
                }

                $parts['dsn'] = $parts['scheme'] . ':host='
                              . $parts['host'] . ';dbname='
                              . $parts['database'];
                
                if (isset($parts['port'])) {
                    // append port to dsn if supplied
                    $parts['dsn'] .= ';port=' . $parts['port'];
                }
                break;
            default:
                throw new Doctrine_Connection_Exception('Unknown driver '.$parts['scheme']);
        }
        $this->pendingAttributes[PDO::ATTR_DRIVER_NAME] = $parts['scheme'];

        return $parts;
    }
    /**
     * getName
     * returns the name of this driver
     *
     * @return string           the name of this driver
     */
    public function getName()
    {
        return $this->driverName;
    }
    /**
     * __get
     * lazy loads given module and returns it
     *
     * @see Doctrine_DataDict
     * @see Doctrine_Expression
     * @see Doctrine_Export
     * @see Doctrine_Transaction
     * @see Doctrine_Connection::$modules       all availible modules
     * @param string $name                      the name of the module to get
     * @throws Doctrine_Connection_Exception    if trying to get an unknown module
     * @return Doctrine_Connection_Module       connection module
     */
    public function __get($name)
    {
        if (isset($this->properties[$name]))
            return $this->properties[$name];

        if ( ! isset($this->modules[$name])) {
            throw new Doctrine_Connection_Exception('Unknown module / property ' . $name);
        }
        if ($this->modules[$name] === false) {
            switch ($name) {
                case 'unitOfWork':
                    $this->modules[$name] = new Doctrine_Connection_UnitOfWork($this);
                    break;
                case 'formatter':
                    $this->modules[$name] = new Doctrine_Formatter($this);
                    break;
                default:
                    $class = 'Doctrine_' . ucwords($name) . '_' . $this->getName();
                    $this->modules[$name] = new $class($this);
                }
        }

        return $this->modules[$name];
    }
    /**
     * returns the manager that created this connection
     *
     * @return Doctrine_Manager
     */
    public function getManager()
    {
        return $this->getParent();
    }
    /**
     * returns the database handler of which this connection uses
     *
     * @return PDO              the database handler
     */
    public function getDbh()
    {
        return $this->dbh;
    }
    /**
     * connect
     * connects into database
     *
     * @return boolean
     */
    public function connect()
    {

        if ($this->isConnected) {
            return false;
        }

        $this->getListener()->onPreConnect($this);

        $e     = explode(':', $this->options['dsn']);
        $found = false;
        
        if (extension_loaded('pdo')) {
            if (in_array($e[0], PDO::getAvailableDrivers())) {
                $this->dbh = new PDO($this->options['dsn'], $this->options['username'], $this->options['password']);
                $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $found = true;
            }
        }

        if ( ! $found) {
            $class = 'Doctrine_Adapter_' . ucwords($e[0]);

            if (class_exists($class)) {
                $this->dbh = new $class($this->options['dsn'], $this->options['username'], $this->options['password']);
            } else {
                throw new Doctrine_Connection_Exception("Couldn't locate driver named " . $e[0]);      	
            }
        }

        // attach the pending attributes to adapter
        foreach($this->pendingAttributes as $attr => $value) {
            // some drivers don't support setting this so we just skip it
            if($attr == Doctrine::ATTR_DRIVER_NAME) {
                continue;
            }
            $this->dbh->setAttribute($attr, $value);
        }

        $this->isConnected = true;

        $this->getListener()->onConnect($this);
        return true;
    }
    /**
     * converts given driver name
     *
     * @param
     */
    public function driverName($name)
    {
    }
    /**
     * supports
     *
     * @param string $feature   the name of the feature
     * @return boolean          whether or not this drivers supports given feature
     */
    public function supports($feature)
    {
        return (isset($this->supported[$feature])
                  && ($this->supported[$feature] === 'emulated'
                   || $this->supported[$feature]));
    }
    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param                   string  name of the table on which the REPLACE query will
     *                          be executed.
     *
     * @param   array           an associative array that describes the fields and the
     *                          values that will be inserted or updated in the specified table. The
     *                          indexes of the array are the names of all the fields of the table.
     *
     *                          The values of the array are values to be assigned to the specified field.
     *
     * @param array $keys       an array containing all key fields (primary key fields
     *                          or unique index fields) for this table
     *
     *                          the uniqueness of a row will be determined according to
     *                          the provided key fields
     *
     *                          this method will fail if no key fields are specified
     *
     * @throws Doctrine_Connection_Exception        if this driver doesn't support replace
     * @throws Doctrine_Connection_Exception        if some of the key values was null
     * @throws Doctrine_Connection_Exception        if there were no key fields
     * @throws PDOException                         if something fails at PDO level
     * @return integer                              number of rows affected
     */
    public function replace($table, array $fields, array $keys)
    {
        //if ( ! $this->supports('replace'))
        //    throw new Doctrine_Connection_Exception('replace query is not supported');

        if (empty($keys)) {
            throw new Doctrine_Connection_Exception('Not specified which fields are keys');
        }
        $condition = $values = array();

        foreach ($fields as $name => $value) {
            $values[$name] = $value;

            if (in_array($name, $keys)) {
                if ($value === null)
                    throw new Doctrine_Connection_Exception('key value '.$name.' may not be null');

                $condition[]       = $name . ' = ?';
                $conditionValues[] = $value;
            }
        }

        $query          = 'DELETE FROM ' . $this->quoteIdentifier($table) . ' WHERE ' . implode(' AND ', $condition);
        $affectedRows   = $this->exec($query);

        $this->insert($table, $values);

        $affectedRows++;


        return $affectedRows;
    }
    /**
     * Inserts a table row with specified data.
     *
     * @param string $table     The table to insert data into.
     * @param array $values     An associateve array containing column-value pairs.
     * @return boolean
     */
    public function insert($table, array $values = array()) {
        if (empty($values)) {
            return false;
        }
        // column names are specified as array keys
        $cols = array_keys($values);

        // build the statement
        $query = 'INSERT INTO ' . $this->quoteIdentifier($table) 
               . '(' . implode(', ', $cols) . ') '
               . 'VALUES (' . substr(str_repeat('?, ', count($values)), 0, -2) . ')';

        // prepare and execute the statement
        $this->execute($query, array_values($values));

        return true;
    }
    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     *
     * @return void
     */
    public function setCharset($charset)
    {

    }
    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str           identifier name to be quoted
     * @param bool $checkOption     check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str, $checkOption = true)
    {
        return $this->formatter->quoteIdentifier($str, $checkOption);
    }
    /**
     * convertBooleans
     * some drivers need the boolean values to be converted into integers
     * when using DQL API
     *
     * This method takes care of that conversion
     *
     * @param array $item
     * @return void
     */
    public function convertBooleans($item)
    {
        return $this->formatter->convertBooleans($item);
    }
    /**
     * quote
     * quotes given input parameter
     *
     * @param mixed $input      parameter to be quoted
     * @param string $type
     * @return mixed
     */
    public function quote($input, $type = null)
    {
        return $this->formatter->quote($input, $type);
    }
    /**
     * Set the date/time format for the current connection
     *
     * @param string    time format
     *
     * @return void
     */
    public function setDateFormat($format = null)
    {
    }
    /**
     * fetchAll
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchAll($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * fetchOne
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @param int $colnum               0-indexed column number to retrieve
     * @return mixed
     */
    public function fetchOne($statement, array $params = array(), $colnum = 0) 
    {
        return $this->execute($statement, $params)->fetchColumn($colnum);
    }
    /**
     * fetchRow
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchRow($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * fetchArray
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchArray($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetch(PDO::FETCH_NUM);
    }
    /**
     * fetchColumn
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @param int $colnum               0-indexed column number to retrieve
     * @return array
     */
    public function fetchColumn($statement, array $params = array(), $colnum = 0) 
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_COLUMN, $colnum);
    }
    /**
     * fetchAssoc
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchAssoc($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * fetchBoth
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchBoth($statement, array $params = array()) 
    {
        return $this->execute($statement, $params)->fetchAll(PDO::FETCH_BOTH);
    }
    /**
     * query
     * queries the database using Doctrine Query Language
     * returns a collection of Doctrine_Record objects
     *
     * <code>
     * $users = $conn->query('SELECT u.* FROM User u');
     *
     * $users = $conn->query('SELECT u.* FROM User u WHERE u.name LIKE ?', array('someone'));
     * </code>
     *
     * @param string $query             DQL query
     * @param array $params             query parameters
     * @see Doctrine_Query
     * @return Doctrine_Collection      Collection of Doctrine_Record objects
     */
    public function query($query, array $params = array()) 
    {
        $parser = new Doctrine_Query($this);

        return $parser->query($query, $params);
    }
    /**
     * query
     * queries the database using Doctrine Query Language and returns
     * the first record found
     *
     * <code>
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.id = ?', array(1));
     *
     * $user = $conn->queryOne('SELECT u.* FROM User u WHERE u.name LIKE ? AND u.password = ?',
     *         array('someone', 'password')
     *         );
     * </code>
     *
     * @param string $query             DQL query
     * @param array $params             query parameters
     * @see Doctrine_Query
     * @return Doctrine_Record|false    Doctrine_Record object on success,
     *                                  boolean false on failure
     */
    public function queryOne($query, array $params = array()) 
    {
        $parser = new Doctrine_Query($this);

        $coll = $parser->query($query, $params);
        if ( ! $coll->contains(0)) {
            return false;
        }
        return $coll[0];
    }
    /**
     * queries the database with limit and offset
     * added to the query and returns a PDOStatement object
     *
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @return PDOStatement
     */
    public function select($query, $limit = 0, $offset = 0)
    {
        if ($limit > 0 || $offset > 0) {
            $query = $this->modifyLimitQuery($query, $limit, $offset);
        }
        return $this->dbh->query($query);
    }
    /**
     * standaloneQuery
     *
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement|Doctrine_Adapter_Statement
     */
    public function standaloneQuery($query, $params = array())
    {
        return $this->execute($query, $params);
    }
    /**
     * execute
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement|Doctrine_Adapter_Statement
     */
    public function execute($query, array $params = array()) 
    {
    	$this->connect();

        try {
            if ( ! empty($params)) {
                $stmt = $this->dbh->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } else {
                return $this->dbh->query($query);
            }
        } catch(Doctrine_Adapter_Exception $e) {
        } catch(PDOException $e) { }

        $this->rethrowException($e);
    }
    /**
     * exec
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement|Doctrine_Adapter_Statement
     */
    public function exec($query, array $params = array()) {
    	$this->connect();

        try {
            if ( ! empty($params)) {
                $stmt = $this->dbh->prepare($query);
                $stmt->execute($params);
                return $stmt->rowCount();
            } else {
                return $this->dbh->exec($query);
            }
        } catch(Doctrine_Adapter_Exception $e) {
        } catch(PDOException $e) { }

        $this->rethrowException($e);
    }
    /**
     * rethrowException
     *
     * @throws Doctrine_Connection_Exception
     */
    private function rethrowException(Exception $e)
    {
        $name = 'Doctrine_Connection_' . $this->driverName . '_Exception';

        $exc  = new $name($e->getMessage(), (int) $e->getCode());
        if ( ! is_array($e->errorInfo)) {
            $e->errorInfo = array(null, null, null, null);
        }
        $exc->processErrorInfo($e->errorInfo);

        throw $exc;
    }
    /**
     * hasTable
     * whether or not this connection has table $name initialized
     *
     * @param mixed $name
     * @return boolean
     */
    public function hasTable($name)
    {
        return isset($this->tables[$name]);
    }
    /**
     * returns a table object for given component name
     *
     * @param string $name              component name
     * @return object Doctrine_Table
     */
    public function getTable($name, $allowExport = true)
    {
        if (isset($this->tables[$name])) {
            return $this->tables[$name];
        }
        $class = $name . 'Table';

        if (class_exists($class) && in_array('Doctrine_Table', class_parents($class))) {
            $table = new $class($name, $this);
        } else {
            $table = new Doctrine_Table($name, $this);
        }

        $this->tables[$name] = $table;


        return $table;
    }
    /**
     * returns an array of all initialized tables
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }
    /**
     * returns an iterator that iterators through all
     * initialized table objects
     *
     * <code>
     * foreach ($conn as $index => $table) {
     *      print $table;  // get a string representation of each table object
     * }
     * </code>
     *
     * @return ArrayIterator        SPL ArrayIterator object
     */
    public function getIterator()
    {
        return new ArrayIterator($this->tables);
    }
    /**
     * returns the count of initialized table objects
     *
     * @return integer
     */
    public function count()
    {
        return count($this->tables);
    }
    /**
     * addTable
     * adds a Doctrine_Table object into connection registry
     *
     * @param $table                a Doctrine_Table object to be added into registry
     * @return boolean
     */
    public function addTable(Doctrine_Table $table)
    {
        $name = $table->getComponentName();

        if (isset($this->tables[$name])) {
            return false;
        }
        $this->tables[$name] = $table;
        return true;
    }
    /**
     * create
     * creates a record
     *
     * create                       creates a record
     * @param string $name          component name
     * @return Doctrine_Record      Doctrine_Record object
     */
    public function create($name)
    {
        return $this->getTable($name)->create();
    }
    /**
     * flush
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     */
    public function flush()
    {
        $this->beginTransaction();
        $this->unitOfWork->saveAll();
        $this->commit();
    }
    /**
     * clear
     * clears all repositories
     *
     * @return void
     */
    public function clear()
    {
        foreach ($this->tables as $k => $table) {
            $table->getRepository()->evictAll();
            $table->clear();
        }
    }
    /**
     * evictTables
     * evicts all tables
     *
     * @return void
     */
    public function evictTables()
    {
        $this->tables = array();
        $this->exported = array();
    }
    /**
     * close
     * closes the connection
     *
     * @return void
     */
    public function close()
    {
        $this->getAttribute(Doctrine::ATTR_LISTENER)->onPreClose($this);

        $this->clear();

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onClose($this);
    }
    /**
     * get the current transaction nesting level
     *
     * @return integer
     */
    public function getTransactionLevel()
    {
        return $this->transaction->getTransactionLevel();
    }
    /**
     * errorCode
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode()
    {
    	$this->connect();

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
    	$this->connect();

        return $this->dbh->errorInfo();
    }
    /**
     * lastInsertId
     *
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers, 
     * because the underlying database may not even support the notion of auto-increment fields or sequences.
     *
     * @param string $table     name of the table into which a new row was inserted
     * @param string $field     name of the field into which a new row was inserted
     */
    public function lastInsertId($table = null, $field = null)
    {
        return $this->sequence->lastInsertId($table, $field);
    }
    /**
     * beginTransaction
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started
     *
     * Listeners: onPreTransactionBegin, onTransactionBegin
     *
     * @param string $savepoint                 name of a savepoint to set
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level
     * @return integer                          current transaction nesting level
     */
    public function beginTransaction($savepoint = null)
    {
        $this->transaction->beginTransaction($savepoint);
    }
    /**
     * commit
     * Commit the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * Listeners: onPreTransactionCommit, onTransactionCommit
     *
     * @param string $savepoint                 name of a savepoint to release
     * @throws Doctrine_Transaction_Exception   if the transaction fails at PDO level
     * @throws Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return boolean                          false if commit couldn't be performed, true otherwise
     */
    public function commit($savepoint = null)
    {
        $this->transaction->commit($savepoint);
    }
    /**
     * rollback
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param string $savepoint                 name of a savepoint to rollback to   
     * @throws Doctrine_Transaction_Exception   if the rollback operation fails at database level
     * @return boolean                          false if rollback couldn't be performed, true otherwise
     */
    public function rollback($savepoint = null)
    {
        $this->transaction->rollback($savepoint);
    }
    /**
     * returns a string representation of this object
     * @return string
     */
    public function __toString()
    {
        return Doctrine_Lib::getConnectionAsString($this);
    }
}

