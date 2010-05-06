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

use Doctrine\DBAL\DBALException;

/**
 * The SqlitePlatform class describes the specifics and dialects of the SQLite
 * database platform.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @todo Rename: SQLitePlatform
 */
class SqlitePlatform extends AbstractPlatform
{
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
     * Trim a string, leading/trailing/both and with a given char which defaults to space.
     *
     * @param string $str
     * @param int $pos
     * @param string $char
     * @return string
     */
    public function getTrimExpression($str, $pos = self::TRIM_UNSPECIFIED, $char = false)
    {
        $trimFn = '';
        $trimChar = ($char != false) ? (', ' . $char) : '';

        if ($pos == self::TRIM_LEADING) {
            $trimFn = 'LTRIM';
        } else if($pos == self::TRIM_TRAILING) {
            $trimFn = 'RTRIM';
        } else {
            $trimFn = 'TRIM';
        }

        return $trimFn . '(' . $str . $trimChar . ')';
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
     * returns the position of the first occurrence of substring $substr in string $str
     *
     * @param string $substr    literal string to find
     * @param string $str       literal string
     * @param int    $pos       position to start at, beginning of string by default
     * @return integer
     */
    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos == false) {
            return 'LOCATE('.$str.', '.$substr.')';
        } else {
            return 'LOCATE('.$str.', '.$substr.', '.$startPos.')';
        }
    }

    protected function _getTransactionIsolationLevelSQL($level)
    {
        switch ($level) {
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED:
                return 0;
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED:
            case \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ:
            case \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE:
                return 1;
            default:
                return parent::_getTransactionIsolationLevelSQL($level);
        }
    }

    public function getSetTransactionIsolationSQL($level)
    {
        return 'PRAGMA read_uncommitted = ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    /** 
     * @override 
     */
    public function prefersIdentityColumns()
    {
        return true;
    }
    
    /** 
     * @override 
     */
    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'BOOLEAN';
    }

    /** 
     * @override 
     */
    public function getIntegerTypeDeclarationSQL(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /** 
     * @override 
     */
    public function getBigIntTypeDeclarationSQL(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /** 
     * @override 
     */
    public function getTinyIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /** 
     * @override 
     */
    public function getSmallIntTypeDeclarationSQL(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /** 
     * @override 
     */
    public function getMediumIntTypeDeclarationSql(array $field)
    {
        return $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    /** 
     * @override 
     */
    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATETIME';
    }
    
    /**
     * @override
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * @override
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    /** 
     * @override 
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        $autoinc = ! empty($columnDef['autoincrement']) ? ' AUTOINCREMENT' : '';
        $pk = ! empty($columnDef['primary']) && ! empty($autoinc) ? ' PRIMARY KEY' : '';

        return 'INTEGER' . $pk . $autoinc;
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
    protected function _getCreateTableSQL($name, array $columns, array $options = array())
    {
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        $autoinc = false;
        foreach($columns as $field) {
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

        $query[] = 'CREATE TABLE ' . $name . ' (' . $queryFields . ')';

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $indexDef) {
                $query[] = $this->getCreateIndexSQL($indexDef, $name);
            }
        }
        if (isset($options['unique']) && ! empty($options['unique'])) {
            foreach ($options['unique'] as $index => $indexDef) {
                $query[] = $this->getCreateIndexSQL($indexDef, $name);
            }
        }
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function getVarcharTypeDeclarationSQL(array $field)
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
    
    public function getClobTypeDeclarationSQL(array $field)
    {
        return 'CLOB';
    }

    public function getListTableConstraintsSQL($table)
    {
        return "SELECT sql FROM sqlite_master WHERE type='index' AND tbl_name = '$table' AND sql NOT NULL ORDER BY name";
    }

    public function getListTableColumnsSQL($table)
    {
        return "PRAGMA table_info($table)";
    }

    public function getListTableIndexesSQL($table)
    {
        return "PRAGMA index_list($table)";
    }

    public function getListTablesSQL()
    {
        return "SELECT name FROM sqlite_master WHERE type = 'table' AND name != 'sqlite_sequence' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type = 'table' ORDER BY name";
    }

    public function getListViewsSQL($database)
    {
        return "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
    }

    public function getCreateViewSQL($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSQL($name)
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

    public function supportsAlterTable()
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

    /**
     * @inheritdoc
     */
    public function getTruncateTableSQL($tableName, $cascade = false)
    {
        return 'DELETE FROM '.$tableName;
    }

    /**
     * User-defined function for Sqlite that is used with PDO::sqliteCreateFunction()
     *
     * @param  int|float $value
     * @return float
     */
    static public function udfSqrt($value)
    {
        return sqrt($value);
    }

    /**
     * User-defined function for Sqlite that implements MOD(a, b)
     */
    static public function udfMod($a, $b)
    {
        return ($a % $b);
    }

    /**
     * @param string $str
     * @param string $substr
     * @param int $offset
     */
    static public function udfLocate($str, $substr, $offset = 0)
    {
        $pos = strpos($str, $substr, $offset);
        if ($pos !== false) {
            return $pos+1;
        }
        return 0;
    }
}
