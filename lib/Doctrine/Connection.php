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
 *    Doctrine_Connection provides many convenience methods.
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
     * @var array $supported                    an array containing all features this driver supports,
     *                                          keys representing feature names and values as
     *                                          one of the following (true, false, 'emulated')
     */
    protected $supported        = array();
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
                             'formatter'   => false
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
    public function __construct(Doctrine_Manager $manager, $adapter)
    {
        if ( ! ($adapter instanceof PDO) && ! in_array('Doctrine_Adapter_Interface', class_implements($adapter))) {
            throw new Doctrine_Connection_Exception("First argument should be an instance of PDO or implement Doctrine_Adapter_Interface");
        }
        $this->dbh   = $adapter;

        //$this->modules['transaction']  = new Doctrine_Connection_Transaction($this);
        $this->modules['unitOfWork']   = new Doctrine_Connection_UnitOfWork($this);

        $this->setParent($manager);

        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->getAttribute(Doctrine::ATTR_LISTENER)->onOpen($this);
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
    public function select($query,$limit = 0,$offset = 0)
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

        if ($allowExport) {
            
            // the following is an algorithm for loading all 
            // the related tables for all loaded tables

            $next = count($this->tables);
            $prev = count($this->exported);
            $stack = $this->exported;
            while ($prev < $next) {
                $prev = count($this->tables);

                foreach($this->tables as $name => $tableObject) {
                    if (isset($stack[$name])) {
                        continue;
                    } else {
                       $stack[$name] = true;
                    }

                    $tableObject->getRelations();
                }
                $next = count($this->tables);
            }


            // when all the tables are loaded we build the array in which the order of the tables is
            // relationally correct so that then those can be created in the given order)

            $names = array_keys($this->tables);

            $names = $this->unitOfWork->buildFlushTree($names);

            foreach($names as $name) {
                $tableObject = $this->tables[$name];

                if (isset($this->exported[$name])) {
                    continue;
                }

                if ($tableObject->getAttribute(Doctrine::ATTR_EXPORT) & Doctrine::EXPORT_TABLES) {

                    $tableObject->export();
                }
                $this->exported[$name] = true;
            }
        }

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
     * @param $objTable             a Doctrine_Table object to be added into registry
     * @return boolean
     */
    public function addTable(Doctrine_Table $objTable)
    {
        $name = $objTable->getComponentName();

        if (isset($this->tables[$name])) {
            return false;
        }
        $this->tables[$name] = $objTable;
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
     * beginTransaction
     * starts a new transaction
     *
     * this method can be listened by onPreBeginTransaction and onBeginTransaction
     * listener methods
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->transaction->beginTransaction();
    }
    /**
     * commits the current transaction
     * if lockmode is optimistic this method starts a transaction
     * and commits it instantly
     *
     * @return void
     */
    public function commit()
    {
        $this->transaction->commit();
    }
    /**
     * rollback
     * rolls back all transactions
     *
     * this method also listens to onPreTransactionRollback and onTransactionRollback
     * eventlisteners
     *
     * @return void
     */
    public function rollback()
    {
        $this->transaction->rollback();
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

