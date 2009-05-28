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
 * The SqlitePlatform class describes the specifics and dialects of the SQLite
 * database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
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

    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED:
                return 0;
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED:
            case \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ:
            case \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE:
                return 1;
            default:
                return parent::_getTransactionIsolationLevelSql($level);
        }
    }

    public function getSetTransactionIsolationSql($level)
    {
        return 'PRAGMA read_uncommitted = ' . $this->_getTransactionIsolationLevelSql($level);
    }

    /** @override */
    public function prefersIdentityColumns()
    {
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
            throw DoctrineException::invalidTableName($name);
        }

        if (empty($fields)) {
            throw DoctrineException::noFieldsSpecifiedForTable($name);
        }
        $queryFields = $this->getColumnDeclarationListSql($fields);

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

    public function getListSequencesSql($database)
    {
        return "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
    }

    public function getListTableConstraintsSql($table)
    {
        return "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name = '$table' AND sql NOT NULL ORDER BY name";
    }

    public function getListTableColumnsSql($table)
    {
        return "PRAGMA table_info($table)";
    }

    public function getListTableIndexesSql($table)
    {
        return "PRAGMA index_list($table)";
    }

    public function getListTablesSql()
    {
        return "SELECT name FROM sqlite_master WHERE type = 'table' AND name != 'sqlite_sequence' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type = 'table' ORDER BY name";
    }

    public function getListTableViews()
    {
        return "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
    }

    public function getListViewsSql()
    {
        return "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
    }

    public function getCreateViewSql($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSql($name)
    {
        return 'DROP VIEW '. $name;
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

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'sqlite';
    }
}