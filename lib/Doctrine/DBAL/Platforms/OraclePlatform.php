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

namespace Doctrine\DBAL\Platforms;

/**
 * OraclePlatform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 */
class OraclePlatform extends AbstractPlatform
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * @todo Doc
     */
    /*private function _createLimitSubquery($query, $limit, $offset, $column = null)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        if (preg_match('/^\s*SELECT/i', $query)) {
            if ( ! preg_match('/\sFROM\s/i', $query)) {
                $query .= " FROM dual";
            }
            if ($limit > 0) {
                $max = $offset + $limit;
                $column = $column === null ? '*' : $column;
                if ($offset > 0) {
                    $min = $offset + 1;
                    $query = 'SELECT b.'.$column.' FROM ('.
                                 'SELECT a.*, ROWNUM AS doctrine_rownum FROM ('
                                   . $query . ') a '.
                              ') b '.
                              'WHERE doctrine_rownum BETWEEN ' . $min .  ' AND ' . $max;
                } else {
                    $query = 'SELECT a.'.$column.' FROM (' . $query .') a WHERE ROWNUM <= ' . $max;
                }
            }
        }
        return $query;
    }*/
    
    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ($length !== null)
            return "SUBSTR($value, $position, $length)";

        return "SUBSTR($value, $position)";
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time:
     * - CURRENT_TIMESTAMP (date and time, TIMESTAMP type)
     * - CURRENT_DATE (date, DATE type)
     * - CURRENT_TIME (time, TIME type)
     *
     * @return string to call a variable with the current timestamp
     * @override
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'date':
            case 'time':
            case 'timestamp':
            default:
                return 'TO_CHAR(CURRENT_TIMESTAMP, \'YYYY-MM-DD HH24:MI:SS\')';
        }
    }

    /**
     * random
     *
     * @return string           an oracle SQL string that generates a float between 0 and 1
     * @override
     */
    public function getRandomExpression()
    {
        return 'dbms_random.value';
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'SYS_GUID()';
    }
    
    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    /*public function getNativeDeclaration(array $field)
    {
        if ( ! isset($field['type'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Missing column type.');
        }
        switch ($field['type']) {
            case 'string':
            case 'array':
            case 'object':
            case 'gzip':
            case 'char':
            case 'varchar':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $this->conn->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR2('.$length.')';
            case 'clob':
                return 'CLOB';
            case 'blob':
                return 'BLOB';
            case 'integer':
            case 'enum':
            case 'int':
                if ( ! empty($field['length'])) {
                    return 'NUMBER('.$field['length'].')';
                }
                return 'INT';
            case 'boolean':
                return 'NUMBER(1)';
            case 'date':
            case 'time':
            case 'timestamp':
                return 'DATE';
            case 'float':
            case 'double':
                return 'NUMBER';
            case 'decimal':
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'NUMBER(*,'.$scale.')';
            default:
        }
        throw \Doctrine\Common\DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }*/

    /**
     * Maps a native array description of a field to a doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @throws Doctrine_DataDict_Oracle_Exception
     * @override
     */
    /*public function getPortableDeclaration(array $field)
    {
        if ( ! isset($field['data_type'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Native oracle definition must have a data_type key specified');
        }
        
        $dbType = strtolower($field['data_type']);
        $type = array();
        $length = $unsigned = $fixed = null;
        if ( ! empty($field['data_length'])) {
            $length = $field['data_length'];
        }

        if ( ! isset($field['column_name'])) {
            $field['column_name'] = '';
        }

        switch ($dbType) {
            case 'integer':
            case 'pls_integer':
            case 'binary_integer':
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['column_name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'varchar':
            case 'varchar2':
            case 'nvarchar2':
                $fixed = false;
            case 'char':
            case 'nchar':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['column_name'])) {
                        $type = array_reverse($type);
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'float':
                $type[] = 'float';
                break;
            case 'number':
                if ( ! empty($field['data_scale'])) {
                    $type[] = 'decimal';
                } else {
                    $type[] = 'integer';
                    if ($length == '1') {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['column_name'])) {
                            $type = array_reverse($type);
                        }
                    }
                }
                break;
            case 'long':
                $type[] = 'string';
            case 'clob':
            case 'nclob':
                $type[] = 'clob';
                break;
            case 'blob':
            case 'raw':
            case 'long raw':
            case 'bfile':
                $type[] = 'blob';
                $length = null;
            break;
            case 'rowid':
            case 'urowid':
            default:
                throw \Doctrine\Common\DoctrineException::updateMe('unknown database attribute type: ' . $dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }*/

    /**
     * {@inheritdoc}
     *
     * @return string
     * @override
     */
    public function getCreateSequenceSql($sequenceName, $start = 1, $allocationSize = 1)
    {
        return 'CREATE SEQUENCE ' . $this->quoteIdentifier($sequenceName)
                . ' START WITH ' . $start . ' INCREMENT BY ' . $allocationSize;
    }
    
    /**
     * {@inheritdoc}
     *
     * @param string $sequenceName
     * @override
     */
    public function getSequenceNextValSql($sequenceName)
    {
        return 'SELECT ' . $this->quoteIdentifier($sequenceName) . '.nextval FROM DUAL';
    }
    
    /**
     * {@inheritdoc}
     *
     * @param integer $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'ALTER SESSION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }
    
    /**
     * Enter description here...
     *
     * @param integer $level
     * @override
     */
    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case Doctrine_DBAL_Connection::TRANSACTION_READ_UNCOMMITTED:
                return 'READ COMMITTED';
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                return 'SERIALIZABLE';
            default:
                return parent::_getTransactionIsolationLevelSql($level);
        }
    }
}