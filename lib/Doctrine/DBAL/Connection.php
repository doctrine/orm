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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL;

use Doctrine\Common\EventManager;
use Doctrine\Common\DoctrineException;

/**
 * A wrapper around a Doctrine\DBAL\Driver\Connection that adds features like
 * events, transaction isolation levels, configuration, emulated transaction nesting,
 * lazy connecting and more.
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
 * Doctrine\DBAL could ship with a simple standard broker that uses a primitive
 * round-robin approach to distribution. User can provide its own brokers.
 */
class Connection
{
    /**
     * Constant for transaction isolation level READ UNCOMMITTED.
     */
    const TRANSACTION_READ_UNCOMMITTED = 1;
    /**
     * Constant for transaction isolation level READ COMMITTED.
     */
    const TRANSACTION_READ_COMMITTED = 2;
    /**
     * Constant for transaction isolation level REPEATABLE READ.
     */
    const TRANSACTION_REPEATABLE_READ = 3;
    /**
     * Constant for transaction isolation level SERIALIZABLE.
     */
    const TRANSACTION_SERIALIZABLE = 4;

    /**
     * The wrapped driver connection.
     *
     * @var Doctrine\DBAL\Driver\Connection
     */
    protected $_conn;

    /**
     * The Configuration.
     *
     * @var Doctrine\DBAL\Configuration
     */
    protected $_config;

    /**
     * The EventManager.
     *
     * @var Doctrine\Common\EventManager
     */
    protected $_eventManager;

    /**
     * Whether or not a connection has been established.
     *
     * @var boolean
     */
    protected $_isConnected = false;

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
     * The parameters used during creation of the Connection instance.
     *
     * @var array
     */
    protected $_params = array();

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
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $_platform;

    /**
     * The schema manager.
     *
     * @var Doctrine\DBAL\Schema\SchemaManager
     */
    protected $_schemaManager;

    /**
     * The used DBAL driver.
     *
     * @var Doctrine\DBAL\Driver
     */
    protected $_driver;

    /**
     * Whether to quote identifiers. Read from the configuration upon construction.
     *
     * @var boolean
     */
    protected $_quoteIdentifiers = false;

    /**
     * Initializes a new instance of the Connection class.
     *
     * @param array $params  The connection parameters.
     * @param Driver $driver
     * @param Configuration $config
     * @param EventManager $eventManager
     */
    public function __construct(array $params, Driver $driver, Configuration $config = null,
            EventManager $eventManager = null)
    {
        $this->_driver = $driver;
        $this->_params = $params;

        if (isset($params['pdo'])) {
            $this->_conn = $params['pdo'];
            $this->_isConnected = true;
        }

        // Create default config and event manager if none given
        if ( ! $config) {
            $config = new Configuration();
        }
        if ( ! $eventManager) {
            $eventManager = new EventManager();
        }

        $this->_config = $config;
        $this->_eventManager = $eventManager;
        $this->_platform = $driver->getDatabasePlatform();
        $this->_transactionIsolationLevel = $this->_platform->getDefaultTransactionIsolationLevel();
        $this->_quoteIdentifiers = $config->getQuoteIdentifiers();
        $this->_platform->setQuoteIdentifiers($this->_quoteIdentifiers);
    }

    /**
     * Get the array of parameters used to instantiated this connection instance
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Get the name of the database connected to for this Connection instance
     *
     * @return string $database
     */
    public function getDatabase()
    {
        return $this->_driver->getDatabase($this);
    }

    /**
     * Gets the DBAL driver instance.
     *
     * @return Doctrine\DBAL\Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Gets the Configuration used by the Connection.
     *
     * @return Doctrine\DBAL\Configuration
     */
    public function getConfiguration()
    {
        return $this->_config;
    }

    /**
     * Gets the EventManager used by the Connection.
     *
     * @return Doctrine\Common\EventManager
     */
    public function getEventManager()
    {
        return $this->_eventManager;
    }

    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }

    /**
     * Establishes the connection with the database.
     *
     * @return boolean
     */
    public function connect()
    {
        if ($this->_isConnected) return false;

        $driverOptions = isset($this->_params['driverOptions']) ?
        $this->_params['driverOptions'] : array();
        $user = isset($this->_params['user']) ?
        $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
        $this->_params['password'] : null;

        $this->_conn = $this->_driver->connect(
        $this->_params,
        $user,
        $password,
        $driverOptions
        );

        $this->_isConnected = true;

        return true;
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetch(PDO::FETCH_ASSOC).
     *
     * @param string $statement The SQL query.
     * @param array $params The query parameters.
     * @return array
     */
    public function fetchRow($statement, array $params = array())
    {
        return $this->execute($statement, $params)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetch(PDO::FETCH_NUM).
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchArray($statement, array $params = array())
    {
        return $this->execute($statement, $params)->fetch(\PDO::FETCH_NUM);
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetchAll(PDO::FETCH_COLUMN, ...).
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @param int $colnum               0-indexed column number to retrieve
     * @return array
     */
    public function fetchColumn($statement, array $params = array(), $colnum = 0)
    {
        return $this->execute($statement, $params)->fetchAll(\PDO::FETCH_COLUMN, $colnum);
    }

    /**
     * Whether an actual connection to the database is established.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetchAll(PDO::FETCH_BOTH).
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchBoth($statement, array $params = array())
    {
        return $this->execute($statement, $params)->fetchAll(\PDO::FETCH_BOTH);
    }

    /**
     * Deletes table row(s) matching the specified identifier.
     *
     * @param string $table         The table to delete data from
     * @param array $identifier     An associateve array containing identifier fieldname-value pairs.
     * @return integer              The number of affected rows
     */
    public function delete($tableName, array $identifier)
    {
        $this->connect();
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
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
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
        $this->_transactionIsolationLevel = $level;
        return $this->exec($this->_platform->getSetTransactionIsolationSql($level));
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
     * Updates table row(s) with specified data
     *
     * @throws Doctrine\DBAL\ConnectionException    if something went wrong at the database level
     * @param string $table     The table to insert data into
     * @param array $values     An associateve array containing column-value pairs.
     * @return mixed            boolean false if empty value array was given,
     *                          otherwise returns the number of affected rows
     */
    public function update($tableName, array $data, array $identifier)
    {
        $this->connect();
        if (empty($data)) {
            return false;
        }

        $set = array();
        foreach ($data as $columnName => $value) {
            $set[] = $this->quoteIdentifier($columnName) . ' = ?';
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
        $this->connect();
        if (empty($data)) {
            return false;
        }

        // column names are specified as array keys
        $cols = array();
        $a = array();
        foreach ($data as $columnName => $value) {
            $cols[] = $this->quoteIdentifier($columnName);
            $a[] = '?';
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
     * Quote a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str           identifier name to be quoted
     * @param bool $checkOption     check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str)
    {
        if ($this->_quoteIdentifiers) {
            return $this->_platform->quoteIdentifier($str);
        }
        return $str;
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input  Parameter to be quoted.
     * @param string $type  Type of the parameter.
     * @return string  The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        $this->connect();
        return $this->_conn->quote($input, $type);
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetchAll(PDO::FETCH_ASSOC).
     *
     * @param string $sql The SQL query.
     * @param array $params The query parameters.
     * @return array
     */
    public function fetchAll($sql, array $params = array())
    {
        return $this->execute($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Convenience method for PDO::query("...") followed by $stmt->fetchColumn().
     *
     * @param string $statement The SQL query.
     * @param array $params The query parameters.
     * @param int $colnum 0-indexed column number to retrieve
     * @return mixed
     */
    public function fetchOne($statement, array $params = array(), $colnum = 0)
    {
        return $this->execute($statement, $params)->fetchColumn($colnum);
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $statement
     * @return Statement
     */
    public function prepare($statement)
    {
        $this->connect();
        return $this->_conn->prepare($statement);
    }

    /**
     * Queries the database with limit and offset added to the query and returns
     * a Statement object.
     *
     * @param string $query
     * @param integer $limit
     * @param integer $offset
     * @return Statement
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
     * @return PDOStatement
     */
    public function execute($query, array $params = array())
    {
        $this->connect();

        if ($this->_config->getSqlLogger()) {
            $this->_config->getSqlLogger()->logSql($query, $params);
        }

        if ( ! empty($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } else {
            $stmt = $this->_conn->query($query);
            $this->_queryCount++;
            return $stmt;
        }
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters.
     *
     * @param string $query     sql query
     * @param array $params     query parameters
     *
     * @return PDOStatement
     * @todo Rename to executeUpdate().
     */
    public function exec($query, array $params = array())
    {
        $this->connect();

        if ($this->_config->getSqlLogger()) {
            $this->_config->getSqlLogger()->logSql($query, $params);
        }

        if ( ! empty($params)) {
            $stmt = $this->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } else {
            $count = $this->_conn->exec($query);
            $this->_queryCount++;
            return $count;
        }
    }

    /**
     * Returns the number of queries executed by the connection.
     *
     * @return integer
     */
    public function getQueryCount()
    {
        return $this->_queryCount;
    }

    /**
     * Returns the current transaction nesting level.
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
        $this->connect();
        return $this->_conn->lastInsertId($seqName);
    }

    /**
     * Start a transaction or set a savepoint.
     *
     * if trying to set a savepoint and there is no active transaction
     * a new transaction is being started.
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        $this->connect();
        if ($this->_transactionNestingLevel == 0) {
            $this->_conn->beginTransaction();
        }
        ++$this->_transactionNestingLevel;
        return true;
    }

    /**
     * Commits the database changes done during a transaction that is in
     * progress or release a savepoint. This function may only be called when
     * auto-committing is disabled, otherwise it will fail.
     *
     * @return boolean FALSE if commit couldn't be performed, TRUE otherwise
     */
    public function commit()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::commitFailedNoActiveTransaction();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            $this->_conn->commit();
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
     * @throws Doctrine\DBAL\ConnectionException   If the rollback operation fails at database level.
     * @return boolean                          FALSE if rollback couldn't be performed, TRUE otherwise.
     */
    public function rollback()
    {
        if ($this->_transactionNestingLevel == 0) {
            throw ConnectionException::rollbackFailedNoActiveTransaction();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {
            $this->_transactionNestingLevel = 0;
            $this->_conn->rollback();
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
     * Gets the wrapped driver connection.
     *
     * @return Doctrine\DBAL\Driver\Connection
     */
    public function getWrappedConnection()
    {
        $this->connect();
        return $this->_conn;
    }

    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @return Doctrine\DBAL\Schema\SchemaManager
     */
    public function getSchemaManager()
    {
        if ( ! $this->_schemaManager) {
            $this->_schemaManager = $this->_driver->getSchemaManager($this);
        }
        return $this->_schemaManager;
    }
}