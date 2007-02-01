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
/**
 * Doctrine_Adapter_Statement
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
interface Doctrine_Adapter_Statement_Interface
{    
    /**
     * bindColumn
     * Bind a column to a PHP variable
     *
     * @param mixed $column   Number of the column (1-indexed) or name of the column in the result set.
     *                        If using the column name, be aware that the name should match
     *                        the case of the column, as returned by the driver.
     * @param string $param   Name of the PHP variable to which the column will be bound.
     * @param integer $type   Data type of the parameter, specified by the Doctrine::PARAM_* constants.
     * @return boolean        Returns TRUE on success or FALSE on failure
     */
    public function bindColumn($column, $param, $type = null);
    /**
     * bindValue
     * Binds a value to a corresponding named or question mark 
     * placeholder in the SQL statement that was use to prepare the statement.
     *
     * @param mixed $param    Parameter identifier. For a prepared statement using named placeholders, 
     *                        this will be a parameter name of the form :name. For a prepared statement 
     *                        using question mark placeholders, this will be the 1-indexed position of the parameter
     * @param mixed $value    The value to bind to the parameter.
     * @param integer $type   Explicit data type for the parameter using the Doctrine::PARAM_* constants.
     * @return boolean        Returns TRUE on success or FALSE on failure.
     */
    public function bindValue($param, $value, $type = null);
    /**
     * bindParam
     * Binds a PHP variable to a corresponding named or question mark placeholder in the 
     * SQL statement that was use to prepare the statement. Unlike Doctrine_Adapter_Statement_Interface->bindValue(), 
     * the variable is bound as a reference and will only be evaluated at the time 
     * that Doctrine_Adapter_Statement_Interface->execute() is called.
     *
     * Most parameters are input parameters, that is, parameters that are 
     * used in a read-only fashion to build up the query. Some drivers support the invocation 
     * of stored procedures that return data as output parameters, and some also as input/output
     * parameters that both send in data and are updated to receive it.
     *
     * @param mixed $param      Parameter identifier. For a prepared statement using named placeholders,
     *                          this will be a parameter name of the form :name. For a prepared statement
     *                          using question mark placeholders, this will be the 1-indexed position of the parameter
     * @param mixed $variable   Name of the PHP variable to bind to the SQL statement parameter.
     * @param integer $type     Explicit data type for the parameter using the Doctrine::PARAM_* constants. To return
     *                          an INOUT parameter from a stored procedure, use the bitwise OR operator to set the
     *                          Doctrine::PARAM_INPUT_OUTPUT bits for the data_type parameter.
     * @param integer $length   Length of the data type. To indicate that a parameter is an OUT parameter 
     *                          from a stored procedure, you must explicitly set the length.
     * @param mixed $driverOptions
     * @return boolean          Returns TRUE on success or FALSE on failure.
     */
    public function bindParam($column, $variable, $type = null, $length = null, $driverOptions);
    public function closeCursor();
    public function fetch();
    public function nextRowset();
    public function execute();
    public function errorCode();
    public function errorInfo();
    public function rowCount();
    public function setFetchMode($mode);
    public function columnCount();
}
