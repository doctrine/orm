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

namespace Doctrine\DBAL\Driver;

use \PDO;

/**
 * Statement interface.
 * Drivers must implement this interface.
 * 
 * This resembles (a subset of) the PDOStatement interface.
 * 
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 */
interface Statement
{
    /**
     * Binds a value to a corresponding named or positional
     * placeholder in the SQL statement that was used to prepare the statement.
     *
     * @param mixed $param          Parameter identifier. For a prepared statement using named placeholders,
     *                              this will be a parameter name of the form :name. For a prepared statement
     *                              using question mark placeholders, this will be the 1-indexed position of the parameter
     *
     * @param mixed $value          The value to bind to the parameter.
     * @param integer $type         Explicit data type for the parameter using the PDO::PARAM_* constants.
     *
     * @return boolean              Returns TRUE on success or FALSE on failure.
     */
    function bindValue($param, $value, $type = null);

    /**
     * Binds a PHP variable to a corresponding named or question mark placeholder in the 
     * SQL statement that was use to prepare the statement. Unlike PDOStatement->bindValue(),
     * the variable is bound as a reference and will only be evaluated at the time 
     * that PDOStatement->execute() is called.
     *
     * Most parameters are input parameters, that is, parameters that are 
     * used in a read-only fashion to build up the query. Some drivers support the invocation 
     * of stored procedures that return data as output parameters, and some also as input/output
     * parameters that both send in data and are updated to receive it.
     *
     * @param mixed $param          Parameter identifier. For a prepared statement using named placeholders,
     *                              this will be a parameter name of the form :name. For a prepared statement
     *                              using question mark placeholders, this will be the 1-indexed position of the parameter
     *
     * @param mixed $variable       Name of the PHP variable to bind to the SQL statement parameter.
     *
     * @param integer $type         Explicit data type for the parameter using the PDO::PARAM_* constants. To return
     *                              an INOUT parameter from a stored procedure, use the bitwise OR operator to set the
     *                              PDO::PARAM_INPUT_OUTPUT bits for the data_type parameter.
     * @return boolean              Returns TRUE on success or FALSE on failure.
     */
    function bindParam($column, &$variable, $type = null);

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean              Returns TRUE on success or FALSE on failure.
     */
    function closeCursor();

    /** 
     * columnCount
     * Returns the number of columns in the result set 
     *
     * @return integer              Returns the number of columns in the result set represented
     *                              by the PDOStatement object. If there is no result set,
     *                              this method should return 0.
     */
    function columnCount();

    /**
     * errorCode
     * Fetch the SQLSTATE associated with the last operation on the statement handle 
     *
     * @see Doctrine_Adapter_Interface::errorCode()
     * @return string       error code string
     */
    function errorCode();

    /**
     * errorInfo
     * Fetch extended error information associated with the last operation on the statement handle
     *
     * @see Doctrine_Adapter_Interface::errorInfo()
     * @return array        error info array
     */
    function errorInfo();

    /**
     * Executes a prepared statement
     *
     * If the prepared statement included parameter markers, you must either:
     * call PDOStatement->bindParam() to bind PHP variables to the parameter markers:
     * bound variables pass their value as input and receive the output value,
     * if any, of their associated parameter markers or pass an array of input-only
     * parameter values
     *
     *
     * @param array $params             An array of values with as many elements as there are
     *                                  bound parameters in the SQL statement being executed.
     * @return boolean                  Returns TRUE on success or FALSE on failure.
     */
    function execute($params = null);

    /**
     * fetch
     *
     * @see Query::HYDRATE_* constants
     * @param integer $fetchStyle           Controls how the next row will be returned to the caller.
     *                                      This value must be one of the Query::HYDRATE_* constants,
     *                                      defaulting to Query::HYDRATE_BOTH
     *
     * @param integer $cursorOrientation    For a PDOStatement object representing a scrollable cursor, 
     *                                      this value determines which row will be returned to the caller. 
     *                                      This value must be one of the Query::HYDRATE_ORI_* constants, defaulting to
     *                                      Query::HYDRATE_ORI_NEXT. To request a scrollable cursor for your 
     *                                      PDOStatement object,
     *                                      you must set the PDO::ATTR_CURSOR attribute to Doctrine::CURSOR_SCROLL when you
     *                                      prepare the SQL statement with Doctrine_Adapter_Interface->prepare().
     *
     * @param integer $cursorOffset         For a PDOStatement object representing a scrollable cursor for which the
     *                                      $cursorOrientation parameter is set to Query::HYDRATE_ORI_ABS, this value specifies
     *                                      the absolute number of the row in the result set that shall be fetched.
     *                                      
     *                                      For a PDOStatement object representing a scrollable cursor for 
     *                                      which the $cursorOrientation parameter is set to Query::HYDRATE_ORI_REL, this value 
     *                                      specifies the row to fetch relative to the cursor position before 
     *                                      PDOStatement->fetch() was called.
     *
     * @return mixed
     */
    function fetch($fetchStyle = PDO::FETCH_BOTH);

    /**
     * Returns an array containing all of the result set rows
     *
     * @param integer $fetchStyle           Controls how the next row will be returned to the caller.
     *                                      This value must be one of the Query::HYDRATE_* constants,
     *                                      defaulting to Query::HYDRATE_BOTH
     *
     * @param integer $columnIndex          Returns the indicated 0-indexed column when the value of $fetchStyle is
     *                                      Query::HYDRATE_COLUMN. Defaults to 0.
     *
     * @return array
     */
    function fetchAll($fetchStyle = PDO::FETCH_BOTH);

    /**
     * fetchColumn
     * Returns a single column from the next row of a
     * result set or FALSE if there are no more rows.
     *
     * @param integer $columnIndex          0-indexed number of the column you wish to retrieve from the row. If no 
     *                                      value is supplied, PDOStatement->fetchColumn() 
     *                                      fetches the first column.
     *
     * @return string                       returns a single column in the next row of a result set.
     */
    function fetchColumn($columnIndex = 0);

    /**
     * rowCount
     * rowCount() returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement 
     * executed by the corresponding object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement, 
     * some databases may return the number of rows returned by that statement. However, 
     * this behaviour is not guaranteed for all databases and should not be 
     * relied on for portable applications.
     *
     * @return integer                      Returns the number of rows.
     */
    function rowCount();
}