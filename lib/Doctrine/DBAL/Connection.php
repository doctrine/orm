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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL;

use PDO, Closure,
    Doctrine\DBAL\Types\Type,
    Doctrine\DBAL\Driver\Connection as DriverConnection,
    Doctrine\Common\EventManager,
    Doctrine\DBAL\DBALException;

/**
 * A wrapper around a Doctrine\DBAL\Driver\Connection that adds features like
 * events, transaction isolation levels, configuration, emulated transaction nesting,
 * lazy connecting and more.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author  Lukas Smith <smith@pooteeweet.org> (MDB2 library)
 */
class Connection implements DriverConnection
{
    /**
     * The wrapped driver connection.
     *
     * @var Doctrine\DBAL\Driver\Connection
     */
    protected $_conn;

    /**
     * @var Doctrine\DBAL\Configuration
     */
    protected $_config;

    /**
     * @var Doctrine\Common\EventManager
     */
    protected $_eventManager;

    /**
     * Whether or not a connection has been established.
     *
     * @var boolean
     */
    private $_isConnected = false;

    /**
     * The parameters used during creation of the Connection instance.
     *
     * @var array
     */
    private $_params = array();

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
     * The DBAL Transaction.
     *
     * @var Doctrine\DBAL\Transaction
     */
    protected $_transaction;
    
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

        if ( ! isset($params['platform'])) {
            $this->_platform = $driver->getDatabasePlatform();
        } else if ($params['platform'] instanceof Platforms\AbstractPlatform) {
            $this->_platform = $params['platform'];
        } else {
            throw DBALException::invalidPlatformSpecified();
        }

        $this->_transaction = new Transaction($this);
    }

    /**
     * Gets the parameters used during instantiation.
     *
     * @return array $params
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string $database
     */
    public function getDatabase()
    {
        return $this->_driver->getDatabase($this);
    }
    
    /**
     * Gets the hostname of the currently connected database.
     * 
     * @return string
     */
    public function getHost()
    {
        return isset($this->_params['host']) ? $this->_params['host'] : null;
    }
    
    /**
     * Gets the port of the currently connected database.
     * 
     * @return mixed
     */
    public function getPort()
    {
        return isset($this->_params['port']) ? $this->_params['port'] : null;
    }
    
    /**
     * Gets the username used by this connection.
     * 
     * @return string
     */
    public function getUsername()
    {
        return isset($this->_params['user']) ? $this->_params['user'] : null;
    }
    
    /**
     * Gets the password used by this connection.
     * 
     * @return string
     */
    public function getPassword()
    {
        return isset($this->_params['password']) ? $this->_params['password'] : null;
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
     * Gets the DBAL Transaction instance.
     *
     * @return Doctrine\DBAL\Transaction
     */
    public function getTransaction()
    {
        return $this->_transaction;
    }

    /**
     * Establishes the connection with the database.
     *
     * @return boolean TRUE if the connection was successfully established, FALSE if
     *                 the connection is already open.
     */
    public function connect()
    {
        if ($this->_isConnected) return false;

        $driverOptions = isset($this->_params['driverOptions']) ?
                $this->_params['driverOptions'] : array();
        $user = isset($this->_params['user']) ? $this->_params['user'] : null;
        $password = isset($this->_params['password']) ?
                $this->_params['password'] : null;

        $this->_conn = $this->_driver->connect($this->_params, $user, $password, $driverOptions);
        $this->_isConnected = true;

        if ($this->_eventManager->hasListeners(Events::postConnect)) {
            $eventArgs = new Event\ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        return true;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     * 
     * @param string $statement The SQL query.
     * @param array $params The query parameters.
     * @return array
     */
    public function fetchAssoc($statement, array $params = array())
    {
        return $this->executeQuery($statement, $params)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @return array
     */
    public function fetchArray($statement, array $params = array())
    {
        return $this->executeQuery($statement, $params)->fetch(PDO::FETCH_NUM);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     * 
     * @param string $statement         sql query to be executed
     * @param array $params             prepared statement params
     * @param int $colnum               0-indexed column number to retrieve
     * @return mixed
     */
    public function fetchColumn($statement, array $params = array(), $colnum = 0)
    {
        return $this->executeQuery($statement, $params)->fetchColumn($colnum);
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
     * Executes an SQL DELETE statement on a table.
     *
     * @param string $table The name of the table on which to delete.
     * @param array $identifier The deletion criteria. An associateve array containing column-value pairs.
     * @return integer The number of affected rows.
     */
    public function delete($tableName, array $identifier)
    {
        $this->connect();

        $criteria = array();

        foreach (array_keys($identifier) as $columnName) {
            $criteria[] = $columnName . ' = ?';
        }

        $query = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $criteria);

        return $this->executeUpdate($query, array_values($identifier));
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
     * Executes an SQL UPDATE statement on a table.
     *
     * @param string $table The name of the table to update.
     * @param array $identifier The update criteria. An associative array containing column-value pairs.
     * @return integer The number of affected rows.
     */
    public function update($tableName, array $data, array $identifier)
    {
        $this->connect();
        $set = array();
        foreach ($data as $columnName => $value) {
            $set[] = $columnName . ' = ?';
        }

        $params = array_merge(array_values($data), array_values($identifier));

        $sql  = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $set)
                . ' WHERE ' . implode(' = ? AND ', array_keys($identifier))
                . ' = ?';

        return $this->executeUpdate($sql, $params);
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param string $table The name of the table to insert data into.
     * @param array $data An associative array containing column-value pairs.
     * @return integer The number of affected rows.
     */
    public function insert($tableName, array $data)
    {
        $this->connect();

        // column names are specified as array keys
        $cols = array();
        $placeholders = array();
        
        foreach ($data as $columnName => $value) {
            $cols[] = $columnName;
            $placeholders[] = '?';
        }

        $query = 'INSERT INTO ' . $tableName
               . ' (' . implode(', ', $cols) . ')'
               . ' VALUES (' . implode(', ', $placeholders) . ')';

        return $this->executeUpdate($query, array_values($data));
    }

    /**
     * Sets the given charset on the current connection.
     *
     * @param string $charset The charset to set.
     */
    public function setCharset($charset)
    {
        $this->executeUpdate($this->_platform->getSetCharsetSQL($charset));
    }

    /**
     * Quote a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use quoted identifiers does not mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The name to be quoted.
     * @return string The quoted name.
     */
    public function quoteIdentifier($str)
    {
        return $this->_platform->quoteIdentifier($str);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed $input Parameter to be quoted.
     * @param string $type Type of the parameter.
     * @return string The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        $this->connect();
        
        return $this->_conn->quote($input, $type);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql The SQL query.
     * @param array $params The query parameters.
     * @return array
     */
    public function fetchAll($sql, array $params = array())
    {
        return $this->executeQuery($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $statement The SQL statement to prepare.
     * @return Doctrine\DBAL\Driver\Statement The prepared statement.
     */
    public function prepare($statement)
    {
        $this->connect();

        return new Statement($statement, $this);
    }

    /**
     * Executes an, optionally parameterized, SQL query.
     *
     * If the query is parameterized, a prepared statement is used.
     * If an SQLLogger is configured, the execution is logged.
     *
     * @param string $query The SQL query to execute.
     * @param array $params The parameters to bind to the query, if any.
     * @return Doctrine\DBAL\Driver\Statement The executed statement.
     * @internal PERF: Directly prepares a driver statement, not a wrapper.
     */
    public function executeQuery($query, array $params = array(), $types = array())
    {
        $this->connect();

        if ($this->_config->getSQLLogger() !== null) {
            $this->_config->getSQLLogger()->logSQL($query, $params);
        }

        if ($params) {
            $stmt = $this->_conn->prepare($query);
            if ($types) {
                $this->_bindTypedValues($stmt, $params, $types);
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }
        } else {
            $stmt = $this->_conn->query($query);
        }

        return $stmt;
    }

    /**
     * Executes an, optionally parameterized, SQL query and returns the result,
     * applying a given projection/transformation function on each row of the result.
     *
     * @param string $query The SQL query to execute.
     * @param array $params The parameters, if any.
     * @param Closure $mapper The transformation function that is applied on each row.
     *                        The function receives a single paramater, an array, that
     *                        represents a row of the result set.
     * @return mixed The projected result of the query.
     */
    public function project($query, array $params, Closure $function)
    {
        $result = array();
        $stmt = $this->executeQuery($query, $params ?: array());

        while ($row = $stmt->fetch()) {
            $result[] = $function($row);
        }

        $stmt->closeCursor();

        return $result;
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     * 
     * @param string $statement
     * @param integer $fetchType
     * @return Doctrine\DBAL\Driver\Statement
     */
    public function query()
    {
        return call_user_func_array(array($this->_conn, 'query'), func_get_args());
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     * 
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $query The SQL query.
     * @param array $params The query parameters.
     * @param array $types The parameter types.
     * @return integer The number of affected rows.
     * @internal PERF: Directly prepares a driver statement, not a wrapper.
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->connect();

        if ($this->_config->getSQLLogger() !== null) {
            $this->_config->getSQLLogger()->logSQL($query, $params);
        }

        if ($params) {
            $stmt = $this->_conn->prepare($query);
            if ($types) {
                $this->_bindTypedValues($stmt, $params, $types);
                $stmt->execute();
            } else {
                $stmt->execute($params);
            }
            $result = $stmt->rowCount();
        } else {
            $result = $this->_conn->exec($query);
        }

        return $result;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     * 
     * @param string $statement
     * @return integer The number of affected rows.
     */
    public function exec($statement)
    {
        $this->connect();
        return $this->_conn->exec($statement);
    }

    /**
     * Fetch the SQLSTATE associated with the last database operation.
     *
     * @return integer The last error code.
     */
    public function errorCode()
    {
        $this->connect();
        return $this->_conn->errorCode();
    }

    /**
     * Fetch extended error information associated with the last database operation.
     *
     * @return array The last error information.
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
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @param string $seqName Name of the sequence object from which the ID should be returned.
     * @return string A string representation of the last inserted ID.
     */
    public function lastInsertId($seqName = null)
    {
        $this->connect();
        return $this->_conn->lastInsertId($seqName);
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

    /**
     * Converts a given value to its database representation according to the conversion
     * rules of a specific DBAL mapping type.
     * 
     * @param mixed $value The value to convert.
     * @param string $type The name of the DBAL mapping type.
     * @return mixed The converted value.
     */
    public function convertToDatabaseValue($value, $type)
    {
        return Type::getType($type)->convertToDatabaseValue($value, $this->_platform);
    }

    /**
     * Converts a given value to its PHP representation according to the conversion
     * rules of a specific DBAL mapping type.
     * 
     * @param mixed $value The value to convert.
     * @param string $type The name of the DBAL mapping type.
     * @return mixed The converted type.
     */
    public function convertToPHPValue($value, $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->_platform);
    }

    /**
     * Binds a set of parameters, some or all of which are typed with a PDO binding type
     * or DBAL mapping type, to a given statement.
     * 
     * @param $stmt The statement to bind the values to.
     * @param array $params The map/list of named/positional parameters.
     * @param array $types The parameter types (PDO binding types or DBAL mapping types).
     * @internal Duck-typing used on the $stmt parameter to support driver statements as well as
     *           raw PDOStatement instances.
     */
    private function _bindTypedValues($stmt, array $params, array $types)
    {
        // Check whether parameters are positional or named. Mixing is not allowed, just like in PDO.
        if (is_int(key($params))) {
            // Positional parameters
            $typeOffset = isset($types[0]) ? -1 : 0;
            $bindIndex = 1;
            foreach ($params as $position => $value) {
                $typeIndex = $bindIndex + $typeOffset;
                if (isset($types[$typeIndex])) {
                    $type = $types[$typeIndex];
                    if (is_string($type)) {
                        $type = Type::getType($type);
                    }
                    if ($type instanceof Type) {
                        $value = $type->convertToDatabaseValue($value, $this->_platform);
                        $bindingType = $type->getBindingType();
                    } else {
                        $bindingType = $type; // PDO::PARAM_* constants
                    }
                    $stmt->bindValue($bindIndex, $value, $bindingType);
                } else {
                    $stmt->bindValue($bindIndex, $value);
                }
                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {
                if (isset($types[$name])) {
                    $type = $types[$name];
                    if (is_string($type)) {
                        $type = Type::getType($type);
                    }
                    if ($type instanceof Type) {
                        $value = $type->convertToDatabaseValue($value, $this->_platform);
                        $bindingType = $type->getBindingType();
                    } else {
                        $bindingType = $type; // PDO::PARAM_* constants
                    }
                    $stmt->bindValue($name, $value, $bindingType);
                } else {
                    $stmt->bindValue($name, $value);
                }
            }
        }
    }
}
