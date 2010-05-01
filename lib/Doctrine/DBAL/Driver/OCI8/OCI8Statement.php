<?php
/*
 *  $Id: Interface.php 3882 2008-02-22 18:11:35Z jwage $
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

namespace Doctrine\DBAL\Driver\OCI8;

use \PDO;

/**
 * The OCI8 implementation of the Statement interface.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
class OCI8Statement implements \Doctrine\DBAL\Driver\Statement
{
    /** Statement handle. */
    private $_sth;
    private $_paramCounter = 0;
    private static $_PARAM = ':param';
    private static $fetchStyleMap = array(
        PDO::FETCH_BOTH => OCI_BOTH,
        PDO::FETCH_ASSOC => OCI_ASSOC,
        PDO::FETCH_NUM => OCI_NUM
    );
    private $_paramMap = array();

    /**
     * Creates a new OCI8Statement that uses the given connection handle and SQL statement.
     *
     * @param resource $dbh The connection handle.
     * @param string $statement The SQL statement.
     */
    public function __construct($dbh, $statement)
    {
        $this->_sth = oci_parse($dbh, $this->_convertPositionalToNamedPlaceholders($statement));
    }

    /**
     * Oracle does not support positional parameters, hence this method converts all
     * positional parameters into artificially named parameters. Note that this conversion
     * is not perfect. All question marks (?) in the original statement are treated as
     * placeholders and converted to a named parameter.
     *
     * @param string $statement The SQL statement to convert.
     * @todo review and test for lost spaces. we experienced missing spaces with oci8 in some sql statements.
     */
    private function _convertPositionalToNamedPlaceholders($statement)
    {
        $count = 1;
        while (($pos = strpos($statement, '?')) !== false) {
            $this->_paramMap[$count] = ":param$count";
            $statement = substr_replace($statement, ":param$count", $pos, 1);
            ++$count;
        }

        return $statement;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = null)
    {
        return $this->bindParam($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($column, &$variable, $type = null)
    {
        $column = isset($this->_paramMap[$column]) ? $this->_paramMap[$column] : $column;
        
        return oci_bind_by_name($this->_sth, $column, $variable);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return boolean              Returns TRUE on success or FALSE on failure.
     */
    public function closeCursor()
    {
        return oci_free_statement($this->_sth);
    }

    /** 
     * {@inheritdoc}
     */
    public function columnCount()
    {
        return oci_num_fields($this->_sth);
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        $error = oci_error($this->_sth);
        if ($error !== false) {
            $error = $error['code'];
        }
        return $error;
    }
    
    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return oci_error($this->_sth);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null)
    {
        if ($params) {
            $hasZeroIndex = isset($params[0]);
            foreach ($params as $key => $val) {
                if ($hasZeroIndex && is_numeric($key)) {
                    $this->bindValue($key + 1, $val);
                } else {
                    $this->bindValue($key, $val);
                }
            }
        }

        $ret = @oci_execute($this->_sth, OCI_DEFAULT);
        if ( ! $ret) {
            throw OCI8Exception::fromErrorInfo($this->errorInfo());
        }
        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchStyle = PDO::FETCH_BOTH)
    {
        if ( ! isset(self::$fetchStyleMap[$fetchStyle])) {
            throw new \InvalidArgumentException("Invalid fetch style: " . $fetchStyle);
        }
        
        return oci_fetch_array($this->_sth, self::$fetchStyleMap[$fetchStyle] | OCI_RETURN_NULLS | OCI_RETURN_LOBS);
    }

    /**
     * {@inheritdoc}
     */
    public function fetchAll($fetchStyle = PDO::FETCH_BOTH)
    {
        if ( ! isset(self::$fetchStyleMap[$fetchStyle])) {
            throw new \InvalidArgumentException("Invalid fetch style: " . $fetchStyle);
        }
        
        $result = array();
        oci_fetch_all($this->_sth, $result, 0, -1, self::$fetchStyleMap[$fetchStyle] | OCI_RETURN_NULLS | OCI_FETCHSTATEMENT_BY_ROW);
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = oci_fetch_row($this->_sth);
        return $row[$columnIndex];
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
        return oci_num_rows($this->_sth);
    }    
}