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

#namespace Doctrine::DBAL::Connections;

#use Doctrine::Common::Configuration;
#use Doctrine::Common::EventManager;
#use Doctrine::DBAL::Exceptions::ConnectionException;

/**
 * A thin wrapper on top of the PDO class.
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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 * @author      Roman Borschel <roman@code-factory.org>
 * @todo
 * 1) REPLICATION SUPPORT
 * Replication support should be tackled at this layer (DBAL).
 * There can be options that look like:
 *       'slaves' => array(
 *           'slave1' => array(
 *                user, pass etc.
 *           ),
 *           'slave2' => array(
 *                user, pass etc.
 *           )),
 *       'slaveConnectionResolver' => new MySlaveConnectionResolver(),
 *       'masters' => array(...),
 *       'masterConnectionResolver' => new MyMasterConnectionResolver()
 * 
 * Doctrine::DBAL could ship with a simple standard broker that uses a primitive
 * round-robin approach to distribution. User can provide its own brokers.
 */
abstract class Doctrine_Connection
{
    /**
     * The PDO database handle. 
     *
     * @var PDO           
     */
    protected $_pdo;
    
    /**
     * The Configuration.
     *
     * @var Doctrine::Common::Configuration
     */
    protected $_config;
    
    /**
     * The EventManager.
     *
     * @var Doctrine::Commom::EventManager
     */
    protected $_eventManager;
    
    /**
     * The name of this connection driver.
     *
     * @var string $driverName                  
     */
    protected $_driverName;
    
    /**
     * Whether or not a connection has been established.
     *
     * @var boolean               
     */
    protected $_isConnected = false;
    
    /**
     * Boolean flag that indicates whether identifiers should get quoted.
     *
     * @var boolean
     */
    protected $_quoteIdentifiers;
    
    /**
     * @var array
     */
    protected $_serverInfo = array();
    
    /**
     * The parameters used during creation of the Connection.
     * 
     * @var array
     */
    protected $_params = array();
    
    /**
     * List of all available drivers.
     * 
     * @var array $availableDrivers
     * @todo Move elsewhere.       
     */
    private static $_availableDrivers = array(
            'Mysql', 'Pgsql', 'Oracle', 'Informix', 'Mssql', 'Sqlite', 'Firebird'
            );
    
    /**
     * The query count. Represents the number of executed database queries by the connection.
     *
     * @var integer
     */
    protected $_queryCount = 0;
    
    /**
     * The DatabasePlatform object that provides information about the
     * database platform used by the connection.
     *
     * @var Doctrine::DBAL::Platforms::DatabasePlatform
     */
    protected $_platform;

    /**
     * The transaction object.
     *
     * @var Doctrine::DBAL::Transaction
     */
    protected $_transaction;
    
    /**
     * The schema manager.
     *
     * @var Doctrine::DBAL::Schema::SchemaManager
     */
    protected $_schemaManager;

    /**
     * Constructor.
     * Creates a new Connection.
     *
     * @param array $params  The connection parameters.
     */
    public function __construct(array $params, $config = null, $eventManager = null)
    {
        if (isset($params['pdo'])) {
            $this->_pdo = $params['pdo'];
            $this->_isConnected = true;
        }
        $this->_params = $params;
        
        // Create default config and event manager if none given
        if ( ! $config) {
            $this->_config = new Doctrine_Configuration();
        }
        if ( ! $eventManager) {
            $this->_eventManager = new Doctrine_EventManager();
        }
        
        // create platform
        $class = "Doctrine_DatabasePlatform_" . $this->_driverName . "Platform";
        $this->_platform = new $class();
        $this->_platform->setQuoteIdentifiers($this->_config->getQuoteIdentifiers());
    }
    
    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        if ( ! $this->_config) {
            $this->_config = new Doctrine_Configuration();
        }
        return $this->_config;
    }
    
    /**
     * Gets the EventManager used by the Connection.
     *
     * @return Doctrine::Common::EventManager
     */
    public function getEventManager()
    {
        if ( ! $this->_eventManager) {
            $this->_eventManager = new Doctrine_EventManager();
        }
        return $this->_eventManager;
    }
    
    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return Doctrine::DBAL::Platforms::DatabasePlatform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }
    
    /**
     * Returns an array of available PDO drivers
     * @todo Move elsewhere.
     */
    public static function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }
    
    /**
     * returns the name of this driver
     *
     * @return string           the name of this driver
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
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
     * Gets the name of the instance driver
     *
     * @return void
     */
    public function getDriverName()
    {
        return $this->_driverName;
    }
    
    /**
     * Gets the PDO handle used by the connection.
     *
     * @return PDO
     */
    public function getPdo()
    {
        $this->connect();
        return $this->_pdo;
    }
    
    /**
     * Establishes the connection with the database.
     *
     * @return boolean
     */
    public function connect()
    {
        if ($this->_isConnected) {
            return false;
        }

        //$event = new Doctrine_Event($this, Doctrine_Event::CONN_CONNECT);
        //$this->getListener()->preConnect($event);

        // TODO: the extension_loaded check can happen earlier, maybe in the factory
        if ( ! extension_loaded('pdo')) {
            throw new Doctrine_Connection_Exception("Couldn't locate driver named " . $e[0]);
        }
        
        $driverOptions = isset($this->_params['driverOptions']) ?
                $this->_params['driverOptions'] : array();
        $user = isset($this->_params['user']) ?
                $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
                $this->_params['password'] : null;
        $this->_pdo = new PDO(
                $this->_constructPdoDsn(),
                $user,
                $password,
                $driverOptions
                );
        $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        // attach the pending attributes to adapter
        /*foreach($this->pendingAttributes as $attr => $value) {
            // some drivers don't support setting this so we just skip it
            if ($attr == Doctrine::ATTR_DRIVER_NAME) {
                continue;
            }
            $this->_pdo->setAttribute($attr, $value);
        }*/

        $this->_isConnected = true;

        //$this->getListener()->postConnect($event);
        return true;
    }
    
    /**
     * Constructs the PDO DSN for use in the PDO constructor.
     * Concrete connection implementations override this implementation to
     * create the proper DSN.
     * 
     * @return string
     * @todo make abstract, implement in subclasses.
     * @todo throw different exception?
     */
    protected function _constructPdoDsn()
    {
        throw Doctrine_Exception::notImplemented('_constructPdoDsn', get_class($this));
    }
    
    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLite implement it natively, this type of
     * query is emulated through this method for other DBMS using standard types
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
            $criteria[] = $this->quoteIdentifier($id) . ' = ?';
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
                $set[] = $this->quoteIdentifier($columnName) . ' = ' . $value->getSql();
                unset($data[$columnName]);
            } else {
                $set[] = $this->quoteIdentifier($columnName) . ' = ?';
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
        // the query VALUES will contain either expressions (eg 'NOW()') or ?
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

        $query = 'INSERT INTO ' . $this->quoteIdentifier($tableName)
               . ' (' . implode(', ', $cols) . ') '
               . 'VALUES (';
        $query .= implode(', ', $a) . ')';

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
     * @todo Moved to DatabasePlatform
     * @deprecated
     */
    public function quoteIdentifier($str)
    {
        /*if (is_null($this->_quoteIdentifiers)) {
            $this->_quoteIdentifiers = $this->_config->get('quoteIdentifiers');
        }
        if ( ! $this->_quoteIdentifiers) {
            return $str;
        }

        // quick fix for the identifiers that contain a dot
        if (strpos($str, '.')) {
            $e = explode('.', $str);
            return $this->quoteIdentifier($e[0])
                    . '.'
                    . $this->quoteIdentifier($e[1]);
        }

        $c = $this->_platform->getIdentifierQuoteCharacter();
        $str = str_replace($c, $c . $c, $str);

        return $c . $str . $c;*/
        return $this->getDatabasePlatform()->quoteIdentifier($str);
    }

    /**
     * Some drivers need the boolean values to be converted into integers
     * when using DQL API.
     *
     * This method takes care of that conversion
     *
     * @param array $item
     * @return void
     * @deprecated Moved to DatabasePlatform
     */
    public function convertBooleans($item)
    {
        if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (is_bool($value)) {
                    $item[$k] = (int) $value;
                }
            }
        } else {
            if (is_bool($item)) {
                $item = (int) $item;
            }
        }
        return $item;
    }

    /**
     * Quotes given input parameter.
     *
     * @param mixed $input  Parameter to be quoted.
     * @param string $type  Type of the parameter.
     * @return string  The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        return $this->_pdo->quote($input, $type);
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
     * prepare
     *
     * @param string $statement
     */
    public function prepare($statement)
    {
        $this->connect();

        try {
            //$event = new Doctrine_Event($this, Doctrine_Event::CONN_PREPARE, $statement);
            //$this->getAttribute(Doctrine::ATTR_LISTENER)->prePrepare($event);

            $stmt = false;
    
            //if ( ! $event->skipOperation) {
                $stmt = $this->_pdo->prepare($statement);
            //}
    
            //$this->getAttribute(Doctrine::ATTR_LISTENER)->postPrepare($event);
            
            return new Doctrine_Connection_Statement($this, $stmt);
        } catch (PDOException $e) {
            $this->rethrowException($e, $this);
        }
    }
    
    /**
     * Queries the database with limit and offset
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
     * Executes an SQL SELECT query with the given parameters.
     * 
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
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } else {
                //$event = new Doctrine_Event($this, Doctrine_Event::CONN_QUERY, $query, $params);
                //$this->getAttribute(Doctrine::ATTR_LISTENER)->preQuery($event);

                //if ( ! $event->skipOperation) {
                    $stmt = $this->_pdo->query($query);
                    $this->_queryCount++;
                //}
                //$this->getAttribute(Doctrine::ATTR_LISTENER)->postQuery($event);

                return $stmt;
            }
        } catch (PDOException $e) {
            $this->rethrowException($e, $this);
        }
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters.
     * 
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement|Doctrine_Adapter_Statement
     * @todo Rename to executeUpdate().
     */
    public function exec($query, array $params = array()) {
        $this->connect();

        try {
            if ( ! empty($params)) {
                $stmt = $this->prepare($query);
                $stmt->execute($params);
                
                return $stmt->rowCount();
            } else {
                //$event = new Doctrine_Event($this, Doctrine_Event::CONN_EXEC, $query, $params);
                //$this->getAttribute(Doctrine::ATTR_LISTENER)->preExec($event);

                //if ( ! $event->skipOperation) {
                    $count = $this->_pdo->exec($query);
                    $this->_queryCount++;
                //}
                //$this->getAttribute(Doctrine::ATTR_LISTENER)->postExec($event);

                return $count;
            }
        } catch (PDOException $e) {
            $this->rethrowException($e, $this);
        }
    }

    /**
     * Wraps the given exception into a driver-specific exception and rethrows it.
     *
     * @throws Doctrine_Connection_Exception
     */
    public function rethrowException(Exception $e, $invoker)
    {        
        $name = 'Doctrine_Connection_' . $this->_driverName . '_Exception';
        $exc = new $name($e->getMessage(), (int) $e->getCode());
        if ( ! is_array($e->errorInfo)) {
            $e->errorInfo = array(null, null, null, null);
        }
        $exc->processErrorInfo($e->errorInfo);
        throw $exc;
    }
    
    /**
     * Returns the number of queries executed by the connection.
     *
     * @return integer
     * @todo Better name: getQueryCount()
     */
    public function getQueryCount()
    {
        return $this->_queryCount;
    }
    
    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        //$event = new Doctrine_Event($this, Doctrine_Event::CONN_CLOSE);
        //this->getAttribute(Doctrine::ATTR_LISTENER)->preClose($event);

        $this->clear();
        unset($this->_pdo);
        $this->_isConnected = false;

        //$this->getAttribute(Doctrine::ATTR_LISTENER)->postClose($event);
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
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode()
    {
        $this->connect();
        
        return $this->_pdo->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo()
    {
        $this->connect();
        
        return $this->_pdo->errorInfo();
    }
    
    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of auto-increment fields or sequences.
     *
     * @param string $table     Name of the table into which a new row was inserted.
     * @param string $field     Name of the field into which a new row was inserted.
     */
    public function lastInsertId($seqName = null)
    {
        return $this->_pdo->lastInsertId($seqName);
    }

    /**
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
        return $this->_transaction->beginTransaction($savepoint);
    }
    
    /**
     * Commits the database changes done during a transaction that is in
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
        return $this->_transaction->commit($savepoint);
    }

    /**
     * Cancel any database changes done during a transaction or since a specific
     * savepoint that is in progress. This function may only be called when
     * auto-committing is disabled, otherwise it will fail. Therefore, a new
     * transaction is implicitly started after canceling the pending changes.
     *
     * this method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods
     *
     * @param string $savepoint                 Name of a savepoint to rollback to.
     * @throws Doctrine_Transaction_Exception   If the rollback operation fails at database level.
     * @return boolean                          FALSE if rollback couldn't be performed, TRUE otherwise.
     */
    public function rollback($savepoint = null)
    {
        $this->_transaction->rollback($savepoint);
    }

    /**
     * Creates the database for the connection instance.
     *
     * @return mixed Will return an instance of the exception thrown if the
     *               create database fails, otherwise it returns a string
     *               detailing the success.
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
     * Method for dropping the database for the connection instance
     *
     * @return mixed Will return an instance of the exception thrown if the drop
     *               database fails, otherwise it returns a string detailing the success.
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
     * Quotes pattern (% and _) characters in a string)
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @param   string  the input string to quote
     *
     * @return  string  quoted string
     */
    protected function _escapePattern($text)
    {
        return $text;
        /*if ( ! $this->string_quoting['escape_pattern']) {
            return $text;
        }
        $tmp = $this->conn->string_quoting;

        $text = str_replace($tmp['escape_pattern'], 
            $tmp['escape_pattern'] .
            $tmp['escape_pattern'], $text);

        foreach ($this->wildcards as $wildcard) {
            $text = str_replace($wildcard, $tmp['escape_pattern'] . $wildcard, $text);
        }
        return $text;*/
    }

    /**
     * Removes any formatting in an sequence name using the 'seqname_format' option
     *
     * @param string $sqn string that containts name of a potential sequence
     * @return string name of the sequence with possible formatting removed
     */
    protected function _fixSequenceName($sqn)
    {
        $seqPattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)',  $this->conn->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT)).'$/i';
        $seqName    = preg_replace($seqPattern, '\\1', $sqn);

        if ($seqName && ! strcasecmp($sqn, $this->getSequenceName($seqName))) {
            return $seqName;
        }
        return $sqn;
    }

    /**
     * Removes any formatting in an index name using the 'idxname_format' option
     *
     * @param string $idx string that containts name of anl index
     * @return string name of the index with possible formatting removed
     */
    protected function _fixIndexName($idx)
    {
        $indexPattern   = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $this->conn->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT)).'$/i';
        $indexName      = preg_replace($indexPattern, '\\1', $idx);
        if ($indexName && ! strcasecmp($idx, $this->getIndexName($indexName))) {
            return $indexName;
        }
        return $idx;
    }

    /**
     * adds sequence name formatting to a sequence name
     *
     * @param string    name of the sequence
     * @return string   formatted sequence name
     */
    protected function _getSequenceName($sqn)
    {
        return sprintf($this->conn->getAttribute(Doctrine::ATTR_SEQNAME_FORMAT),
            preg_replace('/[^a-z0-9_\$.]/i', '_', $sqn));
    }

    /**
     * adds index name formatting to a index name
     *
     * @param string    name of the index
     * @return string   formatted index name
     */
    protected function _getIndexName($idx)
    {
        return sprintf($this->conn->getAttribute(Doctrine::ATTR_IDXNAME_FORMAT),
            preg_replace('/[^a-z0-9_\$]/i', '_', $idx));
    }

    /**
     * adds table name formatting to a table name
     *
     * @param string    name of the table
     * @return string   formatted table name
     */
    protected function _getTableName($table)
    {
        return $table;
        /*
        return sprintf($this->conn->getAttribute(Doctrine::ATTR_TBLNAME_FORMAT),
                $table);*/
    }

    /**
     * returns a string representation of this object
     * @return string
     */
    public function __toString()
    {
        return Doctrine_Lib::getConnectionAsString($this);
    }
    
    /**
     * Gets the SchemaManager that can be used to inspect or change the 
     * database schema through the connection.
     *
     * @return Doctrine::DBAL::Schema::SchemaManager
     */
    public function getSchemaManager()
    {
        if ( ! $this->_schemaManager) {
            $class = "Doctrine_Schema_" . $this->_driverName . "SchemaManager";
            $this->_schemaManager = new $class($this);
        }
        return $this->_schemaManager;
    }
}
