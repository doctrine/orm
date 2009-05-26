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

use Doctrine\Common\DoctrineException;

/**
 * The MsSqlPlatform provides the behavior, features and SQL dialect of the
 * MySQL database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class MsSqlPlatform extends AbstractPlatform
{ 
    /**
     * the constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     * [ borrowed from Zend Framework ]
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     * @link http://lists.bestpractical.com/pipermail/rt-devel/2005-June/007339.html
     * @return string
     * @override
     */
    public function writeLimitClause($query, $limit = false, $offset = false)
    {
        if ($limit > 0) {
            $count = intval($limit);

            $offset = intval($offset);
            if ($offset < 0) {
                throw \Doctrine\Common\DoctrineException::updateMe("LIMIT argument offset=$offset is not valid");
            }
    
            $orderby = stristr($query, 'ORDER BY');
            if ($orderby !== false) {
                $sort = (stripos($orderby, 'desc') !== false) ? 'desc' : 'asc';
                $order = str_ireplace('ORDER BY', '', $orderby);
                $order = trim(preg_replace('/ASC|DESC/i', '', $order));
            }
    
            $query = preg_replace('/^SELECT\s/i', 'SELECT TOP ' . ($count+$offset) . ' ', $query);
    
            $query = 'SELECT * FROM (SELECT TOP ' . $count . ' * FROM (' . $query . ') AS inner_tbl';
            if ($orderby !== false) {
                $query .= ' ORDER BY ' . $order . ' ';
                $query .= (stripos($sort, 'asc') !== false) ? 'DESC' : 'ASC';
            }
            $query .= ') AS outer_tbl';
            if ($orderby !== false) {
                $query .= ' ORDER BY ' . $order . ' ' . $sort;
            }
    
            return $query;

        }

        return $query;
    }

    public function getAlterTableSql($name, array $changes, $check = false)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'rename':
                case 'name':
                    break;
                default:
                    throw \Doctrine\Common\DoctrineException::updateMe('alterTable: change type "' . $changeName . '" not yet supported');
            }
        }

        $query = '';
        if ( ! empty($changes['name'])) {
            $change_name = $this->quoteIdentifier($changes['name']);
            $query .= 'RENAME TO ' . $change_name;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $query .= 'ADD ' . $this->getColumnDeclarationSql($fieldName, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $field_name = $this->quoteIdentifier($fieldName, true);
                $query .= 'DROP COLUMN ' . $fieldName;
            }
        }

        $rename = array();
        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $rename[$field['name']] = $fieldName;
            }
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                if ($query) {
                    $query.= ', ';
                }
                if (isset($rename[$fieldName])) {
                    $oldFieldName = $rename[$fieldName];
                    unset($rename[$fieldName]);
                } else {
                    $oldFieldName = $fieldName;
                }
                $oldFieldName = $this->quoteIdentifier($oldFieldName, true);
                $query .= 'CHANGE ' . $oldFieldName . ' '
                        . $this->getColumnDeclarationSql($fieldName, $field['definition']);
            }
        }

        if ( ! empty($rename) && is_array($rename)) {
            foreach ($rename as $renameName => $renamedField) {
                if ($query) {
                    $query.= ', ';
                }
                $field = $changes['rename'][$renamedField];
                $renamedField = $this->quoteIdentifier($renamedField, true);
                $query .= 'CHANGE ' . $renamedField . ' '
                        . $this->getColumnDeclarationSql($field['name'], $field['definition']);
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->quoteIdentifier($name, true);
        return 'ALTER TABLE ' . $name . ' ' . $query;
    }

    
    /**
     * Gets the character used for identifier quoting.
     *
     * @return string
     * @override
     */
    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }
    
    /**
     * Returns the regular expression operator.
     *
     * @return string
     * @override
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function getRandomExpression()
    {
        return 'RAND()';
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
            case 'time':
            case 'date':
            case 'timestamp':
            default:
                return 'GETDATE()';
        }
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * @return string to call a function to get a substring
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ( ! is_null($length)) {
            return 'SUBSTRING(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTRING(' . $value . ', ' . $position . ', LEN(' . $value . ') - ' . $position . ' + 1)';
    }

    /**
     * Returns string to concatenate two or more string parameters
     *
     * @param string $arg1
     * @param string $arg2
     * @param string $values...
     * @return string to concatenate two strings
     * @override
     */
    public function getConcatExpression()
    {
        $args = func_get_args();
        return '(' . implode(' + ', $args) . ')';
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     * @override
     */
    public function getGuidExpression()
    {
        return 'NEWID()';
    }

    /**
     * Whether the platform prefers identity columns for ID generation.
     * MsSql prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     *
     * @return boolean
     * @override
     */
    public function prefersIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports identity columns.
     * MsSql supports this through AUTO_INCREMENT columns.
     *
     * @return boolean
     * @override
     */
    public function supportsIdentityColumns()
    {
        return true;
    }
    
    /**
     * Whether the platform supports savepoints. MsSql does not.
     *
     * @return boolean
     * @override
     */
    public function supportsSavepoints()
    {
        return false;
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
     *
     * @return      string      DBMS specific SQL code portion that should be used to
     *                          declare the specified field.
     * @override
     */
    public function getNativeDeclaration($field)
    {
        if ( ! isset($field['type'])) {
            throw \Doctrine\Common\DoctrineException::updateMe('Missing column type.');
        }
        switch ($field['type']) {
            case 'array':
            case 'object':
            case 'text':
            case 'char':
            case 'varchar':
            case 'string':
            case 'gzip':
                $length = !empty($field['length'])
                    ? $field['length'] : false;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$this->conn->options['default_text_field_length'].')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return 'VARCHAR('.$length.')';
                    }
                 }
                 return 'TEXT';
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return "VARBINARY($length)";
                    }
                }
                return 'IMAGE';
            case 'integer':
            case 'enum':
            case 'int':
                return 'INT';
            case 'boolean':
                return 'BIT';
            case 'date':
                return 'CHAR(' . strlen('YYYY-MM-DD') . ')';
            case 'time':
                return 'CHAR(' . strlen('HH:MM:SS') . ')';
            case 'timestamp':
                return 'CHAR(' . strlen('YYYY-MM-DD HH:MM:SS') . ')';
            case 'float':
                return 'FLOAT';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }

        throw \Doctrine\Common\DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param   array           $field native field description
     * @return  array           containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration($field)
    {
        $db_type = preg_replace('/[\d\(\)]/','', strtolower($field['type']) );
        $length  = (isset($field['length']) && $field['length'] > 0) ? $field['length'] : null;

        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;

        if ( ! isset($field['name']))
            $field['name'] = '';

        switch ($db_type) {
            case 'bit':
                $type[0] = 'boolean';
            break;
            case 'tinyint':
            case 'smallint':
            case 'int':
                $type[0] = 'integer';
                if ($length == 1) {
                    $type[] = 'boolean';
                }
            break;
            case 'datetime':
                $type[0] = 'timestamp';
            break;
            case 'float':
            case 'real':
            case 'numeric':
                $type[0] = 'float';
            break;
            case 'decimal':
            case 'money':
                $type[0] = 'decimal';
            break;
            case 'text':
            case 'varchar':
            case 'ntext':
            case 'nvarchar':
                $fixed = false;
            case 'char':
            case 'nchar':
                $type[0] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^[is|has]/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($db_type, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'image':
            case 'varbinary':
                $type[] = 'blob';
                $length = null;
            break;
            default:
                throw \Doctrine\Common\DoctrineException::updateMe('unknown database attribute type: '.$db_type);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }

    /**
     * Enter description here...
     *
     * @return unknown
     * @override
     */
    public function getShowDatabasesSql()
    {
        return 'SHOW DATABASES';
    }
    
    /**
     * Enter description here...
     *
     * @todo Throw exception by default?
     * @override
     */
    public function getListTablesSql()
    {
        return 'SHOW TABLES';
    }
    
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return string
     * @override
     */
    public function getCreateDatabaseSql($name)
    {
        return 'CREATE DATABASE ' . $this->quoteIdentifier($name);
    }
    
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return string
     * @override
     */
    public function getDropDatabaseSql($name)
    {
        return 'DROP DATABASE ' . $this->quoteIdentifier($name);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }

    public function getIntegerTypeDeclarationSql(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getBigIntTypeDeclarationSql(array $field)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getSmallIntTypeDeclarationSql(array $field)
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    public function getVarcharTypeDeclarationSql(array $field)
    {
        if ( ! isset($field['length'])) {
            if (array_key_exists('default', $field)) {
                $field['length'] = $this->getVarcharMaxLength();
            } else {
                $field['length'] = false;
            }
        }

        $length = ($field['length'] <= $this->getVarcharMaxLength()) ? $field['length'] : false;
        $fixed = (isset($field['fixed'])) ? $field['fixed'] : false;

        return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
    }

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef)
    {
        $autoinc = '';
        if ( ! empty($columnDef['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        $unsigned = (isset($columnDef['unsigned']) && $columnDef['unsigned']) ? ' UNSIGNED' : '';

        return $unsigned . $autoinc;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }
}