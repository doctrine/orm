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
 * @subpackage  Connection
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class Doctrine_Connection extends Doctrine_Configurable implements Countable, IteratorAggregate
{
    /**
     * The PDO database handle. 
     *
     * @var PDO                 
     */
    protected $dbh;

    /**
     * The metadata factory is used to retrieve the metadata of entity classes.
     *
     * @var Doctrine_ClassMetadata_Factory
     * @todo package:orm
     */
    protected $_metadataFactory;

    /**
     * An array of mapper objects currently maintained by this connection.
     *
     * @var array
     * @todo package:orm 
     */
    protected $_mappers = array();

    /**
     * $_name
     *
     * Name of the connection
     *
     * @var string $_name
     */
    protected $_name;

    /**
     * The name of this connection driver.
     *
     * @var string $driverName                  
     */
    protected $driverName;

    /**
     * Whether or not a connection has been established.
     *
     * @var boolean $isConnected                
     */
    protected $isConnected = false;

    /**
     * An array containing all features this driver supports, keys representing feature
     * names and values as one of the following (true, false, 'emulated').
     *
     * @var array $supported                    
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

    /**
     *
     */
    protected $options    = array();

    /**
     * @var array $availableDrivers         an array containing all available drivers
     */
    private static $availableDrivers = array(
                                        'Mysql',
                                        'Pgsql',
                                        'Oracle',
                                        'Informix',
                                        'Mssql',
                                        'Sqlite',
                                        'Firebird'
                                        );

    /**
     * The query count. Represents the number of executed database queries by the connection.
     *
     * @var integer
     */
    protected $_count = 0;

    /**
     * the constructor
     *
     * @param Doctrine_Manager $manager                 the manager object
     * @param PDO|Doctrine_Adapter_Interface $adapter   database driver
     * @todo Remove the dependency on the Manager for DBAL/ORM separation.
     */
    public function __construct(Doctrine_Manager $manager, $adapter, $user = null, $pass = null)
    {
        if (is_object($adapter)) {
            if ( ! ($adapter instanceof PDO) && ! in_array('Doctrine_Adapter_Interface', class_implements($adapter))) {
                throw new Doctrine_Connection_Exception('First argument should be an instance of PDO or implement Doctrine_Adapter_Interface');
            }
            $this->dbh = $adapter;
            $this->isConnected = true;
        } else if (is_array($adapter)) {
            $this->pendingAttributes[Doctrine::ATTR_DRIVER_NAME] = $adapter['scheme'];

            $this->options['dsn']      = $adapter['dsn'];
            $this->options['username'] = $adapter['user'];
            $this->options['password'] = $adapter['pass'];

            $this->options['other'] = array();
            if (isset($adapter['other'])) {
                $this->options['other'] = array(Doctrine::ATTR_PERSISTENT => $adapter['persistent']);
            }

        }

        $this->setParent($manager);

        $this->setAttribute(Doctrine::ATTR_CASE, Doctrine::CASE_NATURAL);
        $this->setAttribute(Doctrine::ATTR_ERRMODE, Doctrine::ERRMODE_EXCEPTION);

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
    }

    /**
     * getOption
     *
     * Retrieves option
     *
     * @param string $option
     * @return void
     */
    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
    }

    /**
     * setOption
     * 
     * Set option value
     *
     * @param string $option 
     * @return void
     */
    public function setOption($option, $value)
    {
      return $this->options[$option] = $value;
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
                return parent::getAttribute($attribute);
            }
            return $this->attributes[$attribute];
        }

        if ($this->isConnected) {
            try {
                return $this->dbh->getAttribute($attribute);
            } catch (Exception $e) {
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
     * @todo why check for >= 100? has this any special meaning when creating
     * attributes?
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
     * getName
     * returns the name of this driver
     *
     * @return string           the name of this driver
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * setName
     *
     * Sets the name of the connection
     *
     * @param string $name 
     * @return void
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * getDriverName
     *
     * Gets the name of the instance driver
     *
     * @return void
     */
    public function getDriverName()
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
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

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
                    $class = 'Doctrine_' . ucwords($name) . '_' . $this->getDriverName();
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
     * returns the database handler which this connection uses
     *
     * @return PDO              the database handler
     */
    public function getDbh()
    {
        $this->connect();
        
        return $this->dbh;
    }

    /**
     * Establishes the connection with the database.
     *
     * @return boolean
     */
    public function connect()
    {
        if ($this->isConnected) {
            return false;
        }

        $event = new Doctrine_Event($this, Doctrine_Event::CONN_CONNECT);
        $this->getListener()->preConnect($event);

        $e = explode(':', $this->options['dsn']);
        $found = false;

        if (extension_loaded('pdo')) {
            if (in_array($e[0], PDO::getAvailableDrivers())) {
                $this->dbh = new PDO($this->options['dsn'], $this->options['username'],
                                     $this->options['password'], $this->options['other']);

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
            if ($attr == Doctrine::ATTR_DRIVER_NAME) {
                continue;
            }
            $this->dbh->setAttribute($attr, $value);
        }

        $this->isConnected = true;

        $this->getListener()->postConnect($event);
        return true;
    }
    
    /**
     * @todo Remove. Breaks encapsulation.
     */
    public function incrementQueryCount() 
    {
        $this->_count++;
    }

    /**
     * converts given driver name
     *
     * @param
     */
    public function driverName($name)
    {}

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
    public function replace($tableName, array $data, array $keys)
    {
        if (empty($keys)) {
            throw new Doctrine_Connection_Exception('Not specified which fields are keys');
        }
        $condition = $values = array();

        foreach ($data as $columnName => $value) {
            $values[$columnName] = $value;

            if (in_array($columnName, $keys)) {
                if ($value === null)
                    throw new Doctrine_Connection_Exception('key value '.$columnName.' may not be null');

                $condition[] = $columnName . ' = ?';
                $conditionValues[] = $value;
            }
        }

        $query = 'DELETE FROM ' . $this->quoteIdentifier($tableName)
                . ' WHERE ' . implode(' AND ', $condition);
        $affectedRows = $this->exec($query, $conditionValues);

        $this->insert($table, $values);

        $affectedRows++;

        return $affectedRows;
    }

    /**
     * Deletes table row(s) matching the specified identifier.
     *
     * @throws Doctrine_Connection_Exception    if something went wrong at the database level
     * @param string $table         The table to delete data from
     * @param array $identifier     An associateve array containing identifier fieldname-value pairs.
     * @return integer              The number of affected rows
     */
    public function delete($tableName, array $identifier)
    {
        $criteria = array();
        foreach (array_keys($identifier) as $id) {
            $criteria[] = $id . ' = ?';
        }

        $query = 'DELETE FROM '
               . $this->quoteIdentifier($tableName)
               . ' WHERE ' . implode(' AND ', $criteria);

        return $this->exec($query, array_values($identifier));
    }

    /**
     * Updates table row(s) with specified data
     *
     * @throws Doctrine_Connection_Exception    if something went wrong at the database level
     * @param string $table     The table to insert data into
     * @param array $values     An associateve array containing column-value pairs.
     * @return mixed            boolean false if empty value array was given,
     *                          otherwise returns the number of affected rows
     */
    public function update($tableName, array $data, array $identifier)
    {
        if (empty($data)) {
            return false;
        }

        $set = array();
        foreach ($data as $columnName => $value) {
            if ($value instanceof Doctrine_Expression) {
                $set[] = $columnName . ' = ' . $value->getSql();
                unset($data[$columnName]);
            } else {
                $set[] = $columnName . ' = ?';
            }
        }

        $params = array_merge(array_values($data), array_values($identifier));

        $sql  = 'UPDATE ' . $this->quoteIdentifier($tableName)
              . ' SET ' . implode(', ', $set)
              . ' WHERE ' . implode(' = ? AND ', array_keys($identifier))
              . ' = ?';

        return $this->exec($sql, $params);
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param string $table     The table to insert data into.
     * @param array $fields     An associateve array containing fieldname-value pairs.
     * @return mixed            boolean false if empty value array was given,
     *                          otherwise returns the number of affected rows
     */
    public function insert($tableName, array $data)
    {
        if (empty($data)) {
            return false;
        }

        // column names are specified as array keys
        $cols = array();
        // the query VALUES will contain either expresions (eg 'NOW()') or ?
        $a = array();
        foreach ($data as $columnName => $value) {
            $cols[] = $this->quoteIdentifier($columnName);
            if ($value instanceof Doctrine_Expression) {
                $a[] = $value->getSql();
                unset($data[$columnName]);
            } else {
                $a[] = '?';
            }
        }

        // build the statement
        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName)
               . ' (' . implode(', ', $cols) . ') '
               . 'VALUES (';

        $query .= implode(', ', $a) . ')';
        // prepare and execute the statement
        
        return $this->exec($query, array_values($data));
    }

    /**
     * Set the charset on the current connection
     *
     * @param string    charset
     */
    public function setCharset($charset)
    {
        return true;
    }

    /**
     * Quote a string so it can be safely used as a table or column name.
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
        // quick fix for the identifiers that contain a dot
        if (strpos($str, '.')) {
            $e = explode('.', $str);

            return $this->formatter->quoteIdentifier($e[0], $checkOption) . '.'
                 . $this->formatter->quoteIdentifier($e[1], $checkOption);
        }
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
        return $this->execute($statement, $params)->fetchAll(Doctrine::FETCH_ASSOC);
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
        return $this->execute($statement, $params)->fetch(Doctrine::FETCH_ASSOC);
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
        return $this->execute($statement, $params)->fetch(Doctrine::FETCH_NUM);
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
        return $this->execute($statement, $params)->fetchAll(Doctrine::FETCH_COLUMN, $colnum);
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
        return $this->execute($statement, $params)->fetchAll(Doctrine::FETCH_ASSOC);
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
        return $this->execute($statement, $params)->fetchAll(Doctrine::FETCH_BOTH);
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
     * @param int $hydrationMode        Doctrine::FETCH_ARRAY or Doctrine::FETCH_RECORD
     * @see Doctrine_Query
     * @return Doctrine_Collection      Collection of Doctrine_Record objects
     */
    public function query($query, array $params = array(), $hydrationMode = null)
    {
        $parser = new Doctrine_Query($this);

        return $parser->query($query, $params, $hydrationMode);
    }

    /**
     * prepare
     *
     * @param string $statement
     */
    public function prepare($statement)
    {
        $this->connect();

        try {
            $event = new Doctrine_Event($this, Doctrine_Event::CONN_PREPARE, $statement);

            $this->getAttribute(Doctrine::ATTR_LISTENER)->prePrepare($event);

            $stmt = false;

            if ( ! $event->skipOperation) {
                $stmt = $this->dbh->prepare($statement);
            }

            $this->getAttribute(Doctrine::ATTR_LISTENER)->postPrepare($event);

            return new Doctrine_Connection_Statement($this, $stmt);
        } catch(Doctrine_Adapter_Exception $e) {
        } catch(PDOException $e) { }

        $this->rethrowException($e, $this);
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
     * added to the query and returns a Doctrine_Connection_Statement object
     *
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @return Doctrine_Connection_Statement
     */
    public function select($query, $limit = 0, $offset = 0)
    {
        if ($limit > 0 || $offset > 0) {
            $query = $this->modifyLimitQuery($query, $limit, $offset);
        }
        return $this->execute($query);
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
                //echo $query . "<br />";
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } else {
                $event = new Doctrine_Event($this, Doctrine_Event::CONN_QUERY, $query, $params);

                $this->getAttribute(Doctrine::ATTR_LISTENER)->preQuery($event);

                if ( ! $event->skipOperation) {
                    //try {
                        $stmt = $this->dbh->query($query);
                    /*} catch (Exception $e) {
                        if (strstr($e->getMessage(), 'no such column')) {
                            echo $query . "<br /><br />";
                        }
                    }*/

                    $this->_count++;
                }
                $this->getAttribute(Doctrine::ATTR_LISTENER)->postQuery($event);

                return $stmt;
            }
        } catch (Doctrine_Adapter_Exception $e) {
        } catch (PDOException $e) { }

        $this->rethrowException($e, $this);
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
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                return $stmt->rowCount();
            } else {
                $event = new Doctrine_Event($this, Doctrine_Event::CONN_EXEC, $query, $params);

                $this->getAttribute(Doctrine::ATTR_LISTENER)->preExec($event);

                if ( ! $event->skipOperation) {
                    $count = $this->dbh->exec($query);
                    $this->_count++;
                }
                $this->getAttribute(Doctrine::ATTR_LISTENER)->postExec($event);

                return $count;
            }
        } catch (Doctrine_Adapter_Exception $e) {
        } catch (PDOException $e) { }

        $this->rethrowException($e, $this);
    }

    /**
     * rethrowException
     *
     * @throws Doctrine_Connection_Exception
     */
    public function rethrowException(Exception $e, $invoker)
    {
        $event = new Doctrine_Event($this, Doctrine_Event::CONN_ERROR);
        $this->getListener()->preError($event);

        /*if (strstr($e->getMessage(), 'no such column')) {
            echo $e->getMessage() . "<br />" . $e->getTraceAsString() . "<br />";
        }*/
        
        $name = 'Doctrine_Connection_' . $this->driverName . '_Exception';

        $exc = new $name($e->getMessage(), (int) $e->getCode());
        if ( ! is_array($e->errorInfo)) {
            $e->errorInfo = array(null, null, null, null);
        }
        $exc->processErrorInfo($e->errorInfo);

         if ($this->getAttribute(Doctrine::ATTR_THROW_EXCEPTIONS)) {
            throw $exc;
        }

        $this->getListener()->postError($event);
    }

    /**
     * hasTable
     * whether or not this connection has table $name initialized
     *
     * @param mixed $name
     * @return boolean
     * @deprecated
     * @todo package:orm
     */
    public function hasTable($name)
    {
        return isset($this->tables[$name]);
    }

    /**
     * Returns the metadata for a class.
     *
     * @return Doctrine_Metadata
     * @deprecated Use getClassMetadata()
     * @todo package:orm
     */
    public function getMetadata($className)
    {
        return $this->getClassMetadata($className);
    }

    /**
     * Returns the metadata for a class.
     *
     * @return Doctrine_Metadata
     * @todo package:orm
     */
    public function getClassMetadata($className)
    {
        if ( ! $this->_metadataFactory) {
            $this->_metadataFactory = new Doctrine_ClassMetadata_Factory($this,
                    new Doctrine_ClassMetadata_CodeDriver());
        }
        
        return $this->_metadataFactory->getMetadataFor($className);
    }

    /**
     * Sets the driver that is used to obtain metadata informations about entity
     * classes.
     *
     * @param $driver  The driver to use.
     * @todo package:orm
     */
    public function setClassMetadataDriver($driver)
    {
        $this->_metadataFactory->setDriver($driver);
    }
    
    /**
     * Gets a mapper for the specified domain class that is used to map instances of
     * the class between the relational database and their object representation.
     *
     * @param string $entityClassName  The name of the entity class.
     * @return Doctrine_Mapper  The mapper object.
     * @todo package:orm  
     */
    public function getMapper($entityClassName)
    {
        if (isset($this->_mappers[$entityClassName])) {
            return $this->_mappers[$entityClassName];
        }

        $metadata = $this->getClassMetadata($entityClassName);
        $customMapperClassName = $metadata->getCustomMapperClass();
        if ($customMapperClassName !== null) {
            $mapper = new $customMapperClassName($entityClassName, $metadata);
        } else {
            // instantiate correct mapper type
            $inheritanceType = $metadata->getInheritanceType();
            if ($inheritanceType == Doctrine::INHERITANCETYPE_JOINED) {
                $mapper = new Doctrine_Mapper_Joined($entityClassName, $metadata);
            } else if ($inheritanceType == Doctrine::INHERITANCETYPE_SINGLE_TABLE) {
                $mapper = new Doctrine_Mapper_SingleTable($entityClassName, $metadata);
            } else if ($inheritanceType == Doctrine::INHERITANCETYPE_TABLE_PER_CLASS) {
                $mapper = new Doctrine_Mapper_TablePerClass($entityClassName, $metadata);
            } else {
                throw new Doctrine_Connection_Exception("Unknown inheritance type '$inheritanceType'. Can't create mapper.");
            }
        }

        $this->_mappers[$entityClassName] = $mapper;

        return $mapper;
    }

    /**
     * Gets all mappers that are currently maintained by the connection.
     *
     * @todo package:orm
     */
    public function getMappers()
    {
        return $this->_mappers;
    }

    /**
     * returns an iterator that iterates through all
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
     * Returns the number of queries executed by the connection.
     *
     * @return integer
     * @todo Better name: getQueryCount()
     */
    public function count()
    {
        return $this->_count;
    }

    /**
     * create
     * creates a record
     *
     * create                       creates a record
     * @param string $name          component name
     * @return Doctrine_Record      Doctrine_Record object
     * @todo Any strong reasons why this should not be removed?
     * @todo package:orm
     */
    public function create($name)
    {
        return $this->getMapper($name)->create();
    }

    /**
     * Creates a new Doctrine_Query object that operates on this connection.
     * 
     * @return Doctrine_Query
     * @todo package:orm
     */
    public function createQuery($dql = "")
    {
        $query = new Doctrine_Query($this);
        if ( ! empty($dql)) {
            $query->parseQuery($dql);
        }
        
        return $query;
    }

    /**
     * flush
     * saves all the records from all tables
     * this operation is isolated using a transaction
     *
     * @throws PDOException         if something went wrong at database level
     * @return void
     * @todo package:orm
     */
    public function flush()
    {
        $this->beginInternalTransaction();
        $this->unitOfWork->saveAll();
        $this->commit();
    }

    /**
     * clear
     * clears all repositories
     *
     * @return void
     * @todo package:orm
     */
    public function clear()
    {
        foreach ($this->_mappers as $mapper) {
            $mapper->getRepository()->evictAll();
            $mapper->clear();
        }
    }

    /**
     * evictTables
     * evicts all tables
     *
     * @return void
     * @todo package:orm
     */
    public function evictTables()
    {
        $this->tables = array();
        $this->_mappers = array();
        $this->exported = array();
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        $event = new Doctrine_Event($this, Doctrine_Event::CONN_CLOSE);
        $this->getAttribute(Doctrine::ATTR_LISTENER)->preClose($event);

        $this->clear();

        unset($this->dbh);
        $this->isConnected = false;

        $this->getAttribute(Doctrine::ATTR_LISTENER)->postClose($event);
    }

    /**
     * Returns the current total transaction nesting level.
     *
     * @return integer  The nesting level. A value of 0 means theres no active transaction.
     */
    public function getTransactionLevel()
    {
        return $this->transaction->getTransactionLevel();
    }

    /**
     * Returns the current internal transaction nesting level.
     *
     * @return integer  The nesting level. A value of 0 means theres no active transaction.
     * @todo package:orm???
     */
    public function getInternalTransactionLevel()
    {
        return $this->transaction->getInternalTransactionLevel();
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
     * getCacheDriver
     *
     * @return Doctrine_Cache_Interface
     * @deprecated Use getResultCacheDriver()
     */
    public function getCacheDriver()
    {
        return $this->getResultCacheDriver();
    }

    /**
     * getResultCacheDriver
     *
     * @return Doctrine_Cache_Interface
     * @todo package:orm
     */
    public function getResultCacheDriver()
    {
        if ( ! $this->getAttribute(Doctrine::ATTR_RESULT_CACHE)) {
            throw new Doctrine_Exception('Result Cache driver not initialized.');
        }
        
        return $this->getAttribute(Doctrine::ATTR_RESULT_CACHE);
    }

    /**
     * getQueryCacheDriver
     *
     * @return Doctrine_Cache_Interface
     * @todo package:orm
     */
    public function getQueryCacheDriver()
    {
        if ( ! $this->getAttribute(Doctrine::ATTR_QUERY_CACHE)) {
            throw new Doctrine_Exception('Query Cache driver not initialized.');
        }
        
        return $this->getAttribute(Doctrine::ATTR_QUERY_CACHE);
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
     * @param string $table     Name of the table into which a new row was inserted.
     * @param string $field     Name of the field into which a new row was inserted.
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
        return $this->transaction->beginTransaction($savepoint);
    }

    /**
     * Initiates a transaction.
     *
     * This method must only be used by Doctrine itself to initiate transactions.
     * Userland-code must use {@link beginTransaction()}.
     *
     * @todo package:orm???
     */
    public function beginInternalTransaction($savepoint = null)
    {
        return $this->transaction->beginInternalTransaction($savepoint);
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
        return $this->transaction->commit($savepoint);
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
     * createDatabase
     *
     * Method for creating the database for the connection instance
     *
     * @return mixed Will return an instance of the exception thrown if the create database fails, otherwise it returns a string detailing the success
     */
    public function createDatabase()
    {
        try {
            if ( ! $dsn = $this->getOption('dsn')) {
                throw new Doctrine_Connection_Exception('You must create your Doctrine_Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
            }

            $manager = $this->getManager();

            $info = $manager->parsePdoDsn($dsn);
            $username = $this->getOption('username');
            $password = $this->getOption('password');

            // Make connection without database specified so we can create it
            $connect = $manager->openConnection(new PDO($info['scheme'] . ':host=' . $info['host'], $username, $password), 'tmp_connection', false);

            // Create database
            $connect->export->createDatabase($info['dbname']);

            // Close the tmp connection with no database
            $manager->closeConnection($connect);

            // Close original connection
            $manager->closeConnection($this);

            // Reopen original connection with newly created database
            $manager->openConnection(new PDO($info['dsn'], $username, $password), $this->getName(), true);

            return 'Successfully created database for connection "' . $this->getName() . '" named "' . $info['dbname'] . '"';
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * dropDatabase
     *
     * Method for dropping the database for the connection instance
     *
     * @return mixed Will return an instance of the exception thrown if the drop database fails, otherwise it returns a string detailing the success
     */
    public function dropDatabase()
    {
      try {
          if ( ! $dsn = $this->getOption('dsn')) {
              throw new Doctrine_Connection_Exception('You must create your Doctrine_Connection by using a valid Doctrine style dsn in order to use the create/drop database functionality');
          }

          $info = $this->getManager()->parsePdoDsn($dsn);

          $this->export->dropDatabase($info['dbname']);

          return 'Successfully dropped database for connection "' . $this->getName() . '" named "' . $info['dbname'] . '"';
      } catch (Exception $e) {
          return $e;
      }
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
