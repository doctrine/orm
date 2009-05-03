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
 * The SqlitePlatform class describes the specifics and dialects of the SQLite
 * database platform.
 *
 * @since 2.0
 */
class SqlitePlatform extends AbstractPlatform
{
    /**
     * the constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Returns the md5 sum of the data that SQLite's md5() function receives.
     *
     * @param mixed $data
     * @return string
     */
    public static function md5Impl($data)
    {
        return md5($data);
    }

    /**
     * Returns the modules of the data that SQLite's mod() function receives.
     *
     * @param integer $dividend
     * @param integer $divisor
     * @return string
     */
    public static function modImpl($dividend, $divisor)
    {
        return $dividend % $divisor;
    }

    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str that
     * SQLite's locate() function receives
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @return string
     */
    public static function locateImpl($substr, $str)
    {
        return strpos($str, $substr);
    }
    public static function sha1Impl($str)
    {
        return sha1($str);
    }
    public static function ltrimImpl($str)
    {
        return ltrim($str);
    }
    public static function rtrimImpl($str)
    {
        return rtrim($str);
    }
    public static function trimImpl($str)
    {
        return trim($str);
    }

    /**
     * returns the regular expression operator
     *
     * @return string
     * @override
     */
    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    /**
     * Returns a string to call a function to compute the
     * soundex encoding of a string
     *
     * The string "?000" is returned if the argument is NULL.
     *
     * @param string $value
     * @return string   SQL soundex function with given parameter
     */
    public function getSoundexExpression($value)
    {
        return 'SOUNDEX(' . $value . ')';
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time.
     *
     * @return string       sqlite function as string
     * @override
     */
    public function getNowExpression($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
                return 'time(\'now\')';
            case 'date':
                return 'date(\'now\')';
            case 'timestamp':
            default:
                return 'datetime(\'now\')';
        }
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     * @override
     */
    public function getRandomExpression()
    {
        return '((RANDOM() + 2147483648) / 4294967296)';
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * SQLite only supports the 2 parameter variant of this function
     *
     * @param string $value         an sql string literal or column name/alias
     * @param integer $position     where to start the substring portion
     * @param integer $length       the substring portion length
     * @return string               SQL substring function with given parameters
     * @override
     */
    public function getSubstringExpression($value, $position, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTR(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTR(' . $value . ', ' . $position . ', LENGTH(' . $value . '))';
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
     * @author Lukas Smith (PEAR MDB2 library)
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @override
     */
    public function getNativeDeclaration(array $field)
    {
        if ( ! isset($field['type'])) {
            throw DoctrineException::updateMe('Missing column type.');
        }
        switch ($field['type']) {
            case 'text':
            case 'object':
            case 'array':
            case 'string':
            case 'char':
            case 'gzip':
            case 'varchar':
                $length = (isset($field['length']) && $field['length']) ? $field['length'] : null;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$this->conn->getAttribute(Doctrine::ATTR_DEFAULT_TEXTFLD_LENGTH).')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65535) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                return 'LONGTEXT';
            case 'blob':
                if ( ! empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65535) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'enum':
            case 'integer':
            case 'boolean':
            case 'int':
                return 'INTEGER';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
            case 'double':
                return 'DOUBLE';//($this->conn->options['fixed_float'] ? '('.
                    //($this->conn->options['fixed_float']+2).','.$this->conn->options['fixed_float'].')' : '');
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }
        throw DoctrineException::updateMe('Unknown field type \'' . $field['type'] .  '\'.');
    }

    /**
     * Maps a native array description of a field to Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @override
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $length = (isset($field['length'])) ? $field['length'] : null;
        $unsigned = (isset($field['unsigned'])) ? $field['unsigned'] : null;
        $fixed = null;
        $type = array();

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'boolean':
                $type[] = 'boolean';
                break;
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 1;
                break;
            case 'smallint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 2;
                break;
            case 'mediumint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 3;
                break;
            case 'int':
            case 'integer':
            case 'serial':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
                break;
            case 'bigint':
            case 'bigserial':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 8;
                break;
            case 'clob':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'varchar':
            case 'varchar2':
                $fixed = false;
            case 'char':
                $type[] = 'text';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'datetime':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'double':
            case 'real':
                $type[] = 'float';
                $length = null;
                break;
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $length = null;
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type[] = 'blob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            default:
                throw DoctrineException::updateMe('unknown database attribute type: '.$dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case Doctrine_DBAL_Connection::TRANSACTION_READ_UNCOMMITTED:
                return 0;
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                return 1;
            default:
                return parent::_getTransactionIsolationLevelSql($level);
        }
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'PRAGMA read_uncommitted = ' . $this->_getTransactionIsolationLevelSql($level);
    }

    /** @override */
    public function prefersIdentityColumns() {
        return true;
    }

    /** @override */
    public function getIntegerTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getBigIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getTinyIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getSmallIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    public function getMediumIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSql($field);
    }

    /** @override */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef)
    {
        $autoinc = ! empty($columnDef['autoincrement']) ? ' AUTOINCREMENT' : '';
        $pk = ! empty($columnDef['primary']) && ! empty($autoinc) ? ' PRIMARY KEY' : '';

        return "INTEGER" . $pk . $autoinc;
    }

    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     *                       The indexes of the array entries are the names of the fields of the table an
     *                       the array entry values are associative arrays like those that are meant to be
     *                       passed with the field definitions to get[Type]Declaration() functions.
     *                          array(
     *                              'id' => array(
     *                                  'type' => 'integer',
     *                                  'unsigned' => 1
     *                                  'notnull' => 1
     *                                  'default' => 0
     *                              ),
     *                              'name' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              ),
     *                              'password' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              )
     *                          );
     * @param array $options  An associative array of table options:
     *
     * @return void
     * @override
     */
    public function getCreateTableSql($name, array $fields, array $options = array())
    {
        if ( ! $name) {
            throw ConnectionException::invalidTableName($name);
        }

        if (empty($fields)) {
            throw ConnectionException::noFieldsSpecifiedForTable($name);
        }
        $queryFields = $this->getFieldDeclarationListSql($fields);

        $autoinc = false;
        foreach($fields as $field) {
            if (isset($field['autoincrement']) && $field['autoincrement']) {
                $autoinc = true;
                break;
            }
        }

        if ( ! $autoinc && isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $keyColumns = array_map(array($this, 'quoteIdentifier'), $keyColumns);
            $queryFields.= ', PRIMARY KEY('.implode(', ', $keyColumns).')';
        }

        $name  = $this->quoteIdentifier($name, true);
        $sql   = 'CREATE TABLE ' . $name . ' (' . $queryFields;

        /*if ($check = $this->getCheckDeclarationSql($fields)) {
            $sql .= ', ' . $check;
        }

        if (isset($options['checks']) && $check = $this->getCheckDeclarationSql($options['checks'])) {
            $sql .= ', ' . $check;
        }*/

        $sql .= ')';

        $query[] = $sql;

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $query[] = $this->getCreateIndexSql($name, $index, $definition);
            }
        }
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharDeclarationSql(array $field)
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

    /**
     * SQLite does support foreign key constraints, but only in CREATE TABLE statements...
     * This really limits their usefulness and requires SQLite specific handling, so
     * we simply say that SQLite does NOT support foreign keys for now...
     *
     * @return boolean FALSE
     * @override
     */
    public function supportsForeignKeyConstraints()
    {
        return false;
    }
}