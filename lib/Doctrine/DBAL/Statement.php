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

use PDO,
    Doctrine\DBAL\Types\Type,
    Doctrine\DBAL\Driver\Statement as DriverStatement;

/**
 * A thin wrapper around a Doctrine\DBAL\Driver\Statement that adds support
 * for logging, DBAL mapping types, etc.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Statement implements DriverStatement
{
    /**
     * @var string The SQL statement.
     */
    private $_sql;
    /**
     * @var array The bound parameters.
     */
    private $_params = array();
    /**
     * @var Doctrine\DBAL\Driver\Statement The underlying driver statement.
     */
    private $_stmt;
    /**
     * @var Doctrine\DBAL\Platforms\AbstractPlatform The underlying database platform.
     */
    private $_platform;
    /**
     * @var Doctrine\DBAL\Connection The connection this statement is bound to and executed on.
     */
    private $_conn;

    /**
     * Creates a new <tt>Statement</tt> for the given SQL and <tt>Connection</tt>.
     *
     * @param string $sql The SQL of the statement.
     * @param Doctrine\DBAL\Connection The connection on which the statement should be executed.
     */
    public function __construct($sql, Connection $conn)
    {
        $this->_sql = $sql;
        $this->_stmt = $conn->getWrappedConnection()->prepare($sql);
        $this->_conn = $conn;
        $this->_platform = $conn->getDatabasePlatform();
    }

    /**
     * Binds a parameter value to the statement.
     * 
     * The value can optionally be bound with a PDO binding type or a DBAL mapping type.
     * If bound with a DBAL mapping type, the binding type is derived from the mapping
     * type and the value undergoes the conversion routines of the mapping type before
     * being bound.
     * 
     * @param $name The name or position of the parameter.
     * @param $value The value of the parameter.
     * @param mixed $type Either a PDO binding type or a DBAL mapping type name or instance.
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function bindValue($name, $value, $type = null)
    {
        $this->_params[$name] = $value;
        if ($type !== null) {
            if (is_string($type)) {
                $type = Type::getType($type);
            }
            if ($type instanceof Type) {
                $value = $type->convertToDatabaseValue($value, $this->_platform);
                $bindingType = $type->getBindingType();
            } else {
                $bindingType = $type; // PDO::PARAM_* constants
            }
            return $this->_stmt->bindValue($name, $value, $bindingType);
        } else {
            return $this->_stmt->bindValue($name, $value);
        }
    }

    /**
     * Binds a parameter to a value by reference.
     * 
     * Binding a parameter by reference does not support DBAL mapping types.
     * 
     * @param string $name The name or position of the parameter.
     * @param mixed $value The reference to the variable to bind
     * @param integer $type The PDO binding type.
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function bindParam($name, &$var, $type = PDO::PARAM_STR)
    {
        return $this->_stmt->bindParam($name, $var, $type);
    }

    /**
     * Executes the statement with the currently bound parameters.
     * 
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function execute($params = null)
    {
        if ($this->_conn->getConfiguration()->getSQLLogger()) {
            $this->_conn->getConfiguration()->getSQLLogger()->logSQL($this->_sql, $this->_params);
        }
        $this->_params = array();
        return $this->_stmt->execute($params);
    }

    /**
     * Closes the cursor, freeing the database resources used by this statement. 
     * 
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function closeCursor()
    {
        return $this->_stmt->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     * 
     * @return integer
     */
    public function columnCount()
    {
        return $this->_stmt->columnCount();
    }

    /**
     * Fetches the SQLSTATE associated with the last operation on the statement.
     * 
     * @return string
     */
    public function errorCode()
    {
        return $this->_stmt->errorCode();
    }

    /**
     * Fetches extended error information associated with the last operation on the statement.
     * 
     * @return array
     */
    public function errorInfo()
    {
        return $this->_stmt->errorInfo();
    }

    /**
     * Fetches the next row from a result set.
     * 
     * @param integer $fetchStyle
     * @return mixed The return value of this function on success depends on the fetch type.
     *               In all cases, FALSE is returned on failure.
     */
    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        return $this->_stmt->fetch($fetchStyle);
    }

    /**
     * Returns an array containing all of the result set rows.
     * 
     * @param integer $fetchStyle
     * @param integer $columnIndex
     * @return array An array containing all of the remaining rows in the result set.
     */
    public function fetchAll($fetchStyle = PDO::FETCH_BOTH, $columnIndex = 0)
    {
        if ($columnIndex != 0) {
            return $this->_stmt->fetchAll($fetchStyle, $columnIndex);
        }
        return $this->_stmt->fetchAll($fetchStyle);
    }

    /**
     * Returns a single column from the next row of a result set.
     * 
     * @param integer $columnIndex
     * @return mixed A single column from the next row of a result set or FALSE if there are no more rows. 
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->_stmt->fetchColumn($columnIndex);
    }

    /**
     * Returns the number of rows affected by the last execution of this statement.
     * 
     * @return integer The number of affected rows.
     */
    public function rowCount()
    {
        return $this->_stmt->rowCount();
    }

    /**
     * Gets the wrapped driver statement.
     * 
     * @return Doctrine\DBAL\Driver\Statement
     */
    public function getWrappedStatement()
    {
        return $this->_stmt;
    }
}