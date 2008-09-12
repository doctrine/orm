<?php
/*
 *  $Id: Connection.php 4933 2008-09-12 10:58:33Z romanb $
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

#namespace Doctrine::DBAL;

#use Doctrine::Common::Configuration;
#use Doctrine::Common::EventManager;
#use Doctrine::DBAL::Exceptions::ConnectionException;

/**
 * A wrapper around a Doctrine::DBAL::Connection that adds features like
 * events, transaction isolation levels, configuration, emulated transaction nesting
 * and more.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision: 4933 $
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
 * @todo Rename to ConnectionWrapper
 */
class Doctrine_DBAL_Connection
{
    const TRANSACTION_READ_UNCOMMITTED = 1;
    const TRANSACTION_READ_COMMITTED = 2;
    const TRANSACTION_REPEATABLE_READ = 3;
    const TRANSACTION_SERIALIZABLE = 4;
    
    /**
     * The wrapped driver connection. 
     *
     * @var Doctrine::DBAL::Driver::Connection          
     */
    protected $_conn;
    
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
     * The transaction nesting level.
     *
     * @var integer
     */
    protected $_transactionNestingLevel = 0;
    
    /**
     * The currently active transaction isolation level.
     *
     * @var integer
     */
    protected $_transactionIsolationLevel;
    
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
    public function __construct(array $params, Doctrine_DBAL_Driver $driver,
            Doctrine_Common_Configuration $config = null, Doctrine_Common_EventManager $eventManager = null)
    {
        $this->_driver = $driver;
        $this->_params = $params;
        
        if (isset($params['pdo'])) {
            $this->_conn = $params['pdo'];
            $this->_isConnected = true;
        }
        
        // Create default config and event manager if none given
        if ( ! $config) {
            $this->_config = new Doctrine_Common_Configuration();
        }
        if ( ! $eventManager) {
            $this->_eventManager = new Doctrine_Common_EventManager();
        }

        $this->_platform = $driver->getDatabasePlatform();
        $this->_transactionIsolationLevel = $this->_platform->getDefaultTransactionIsolationLevel();
    }
    
    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->_config;
    }
    
    /**
     * Gets the EventManager used by the Connection.
     *
     * @return Doctrine::Common::EventManager
     */
    public function getEventManager()
    {
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
        return $this->_conn;
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
        $this->_conn = new PDO(
                $this->_constructPdoDsn(),
                $user,
                $password,
                $driverOptions
                );
        $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->_isConnected = true;

        return true;
    }
    
    /**
     * Establishes the connection with the database.
     *
     * @return boolean
     */
    public function connect2()
    {
        if ($this->_isConnected) {
            return false;
        }
        
        $driverOptions = isset($this->_params['driverOptions']) ?
                $this->_params['driverOptions'] : array();
        $user = isset($this->_params['user']) ?
                $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
                $this->_params['password'] : null;
                
        $this->_conn = $this->_driver->connect($this->_params, $user, $password, $driverOptions);
        
        $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->_conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->_isConnected = true;

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
        $this->exec($this->_platform->getSetCharsetSql($charset));
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
        return $this->_platform->quoteIdentifier($str);
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
        return $this->_conn->quote($input, $type);
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
            $stmt = $this->_conn->prepare($statement);
            return new Doctrine_DBAL_Statement($this, $stmt);
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
            $query = $this->_platform->modifyLimitQuery($query, $limit, $offset);
        }
        return $this->execute($query);
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
                $stmt = $this->_conn->query($query);
                $this->_queryCount++;
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
                $count = $this->_conn->exec($query);
                $this->_queryCount++;
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
        $this->clear();
        unset($this->_conn);
        $this->_isConnected = false;
    }
    
    /**
     * Sets the transaction isolation level.
     *
     * @param integer $level The level to set.
     */
    public function setTransactionIsolation($level)
    {
        $sql = "";
        switch ($level) {
            case Doctrine_DBAL_Connection::TRANSACTION_READ_UNCOMMITTED:
                $sql = $this->_platform->getSetTransactionIsolationReadUncommittedSql();
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
                $sql = $this->_platform->getSetTransactionIsolationReadCommittedSql();
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
                $sql = $this->_platform->getSetTransactionIsolationRepeatableReadSql();
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                $sql = $this->_platform->getSetTransactionIsolationSerializableSql();
                break;
            default:
                throw new Doctrine_Common_Exceptions_DoctrineException('isolation level is not supported: ' . $isolation);
        }
        $this->_transactionIsolationLevel = $level;
        
        return $this->exec($sql);
    }
    
    /**
     * Gets the currently active transaction isolation level.
     *
     * @return integer The current transaction isolation level.
     */
    public function getTransactionIsolation()
    {
        return $this->_transactionIsolationLevel;
    }

    /**
     * Returns the current total transaction nesting level.
     *
     * @return integer  The nesting level. A value of 0 means theres no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->_transactionNestingLevel;
    }
    
    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode()
    {
        $this->connect();
        return $this->_conn->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo()
    {
        $this->connect();
        return $this->_conn->errorInfo();
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
        return $this->_conn->lastInsertId($seqName);
    }

    /**
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started.
     *
     * @param string $savepoint                 name of a savepoint to set
     * @throws Doctrine_Transaction_Exception   if the transaction fails at database level
     * @return integer                          current transaction nesting level
     */
    public function beginTransaction()
    {
        if ($this->_transactionNestingLevel == 0) {
            return $this->_conn->beginTransaction();
        }
        ++$this->_transactionNestingLevel;
        return true;
    }
    
    /**
     * Commits the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * @param string $savepoint                 name of a savepoint to release
     * @throws Doctrine_Transaction_Exception   if the transaction fails at PDO level
     * @throws Doctrine_Validator_Exception     if the transaction fails due to record validations
     * @return boolean                          false if commit couldn't be performed, true otherwise
     */
    public function commit()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw new Doctrine_Exception("Commit failed. There is no active transaction.");
        }
        
        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            return $this->_conn->commit();
        }
        --$this->_transactionNestingLevel;
        
        return true;
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
    public function rollback()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw new Doctrine_Exception("Rollback failed. There is no active transaction.");
        }
        
        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            $this->_transactionNestingLevel = 0;
            return $this->_conn->rollback();
            
        }
        --$this->_transactionNestingLevel;
        
        return true;
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
     * Gets the wrapped driver connection.
     *
     * @return Doctrine::DBAL::Driver::Connection
     */
    public function getWrappedConnection()
    {
        return $this->_conn;
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
            $this->_schemaManager = $this->_driver->getSchemaManager();
        }
        return $this->_schemaManager;
    }
}
