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
 * @author Benjamin Eberlei <kontakt@beberlei.de>
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
        if ($length !== null) {
            return "SUBSTR($value, $position, $length)";
        }

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
     * Gets the SQL used to create a sequence that starts with a given value
     * and increments by the given allocation size.
     *
     * @param string $sequenceName
     * @param integer $start
     * @param integer $allocationSize
     * @return string The SQL.
     */
    public function getCreateSequenceSql($sequenceName, $start = 1, $allocationSize = 1)
    {
        return 'CREATE SEQUENCE ' . $sequenceName 
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
        return 'SELECT ' . $sequenceName . '.nextval FROM DUAL';
    }
    
    /**
     * {@inheritdoc}
     *
     * @param integer $level
     * @override
     */
    public function getSetTransactionIsolationSql($level)
    {
        return 'SET TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSql($level);
    }

    protected function _getTransactionIsolationLevelSql($level)
    {
        switch ($level) {
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_UNCOMMITTED:
                return 'READ UNCOMMITTED';
            case \Doctrine\DBAL\Connection::TRANSACTION_READ_COMMITTED:
                return 'READ COMMITTED';
            case \Doctrine\DBAL\Connection::TRANSACTION_REPEATABLE_READ:
            case \Doctrine\DBAL\Connection::TRANSACTION_SERIALIZABLE:
                return 'SERIALIZABLE';
            default:
                return parent::_getTransactionIsolationLevelSql($level);
        }
    }
    
    /**
     * @override
     */
    public function getBooleanTypeDeclarationSql(array $field)
    {
        return 'NUMBER(1)';
    }

    /**
     * @override
     */
    public function getIntegerTypeDeclarationSql(array $field)
    {
        return 'NUMBER(10)';
    }

    /**
     * @override
     */
    public function getBigIntTypeDeclarationSql(array $field)
    {
        return 'NUMBER(20)';
    }

    /**
     * @override
     */
    public function getSmallIntTypeDeclarationSql(array $field)
    {
        return 'NUMBER(5)';
    }

    /**
     * @override
     */
    public function getDateTimeTypeDeclarationSql(array $fieldDeclaration)
    {
        return 'TIMESTAMP(0) WITH TIME ZONE';
    }

    /**
     * @override
     */
    protected function _getCommonIntegerTypeDeclarationSql(array $columnDef)
    {
        return '';
    }

    /**
     * Gets the SQL snippet used to declare a VARCHAR column on the Oracle platform.
     *
     * @params array $field
     * @override
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

        return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(2000)')
                : ($length ? 'VARCHAR2(' . $length . ')' : 'VARCHAR2(4000)');
    }
    
    /** @override */
    public function getClobTypeDeclarationSql(array $field)
    {
        return 'CLOB';
    }

    public function getListDatabasesSql()
    {
        return 'SELECT username FROM sys.dba_users';
    }

    public function getListFunctionsSql()
    {
        return "SELECT name FROM sys.user_source WHERE line = 1 AND type = 'FUNCTION'";
    }

    public function getListSequencesSql($database)
    {
        return 'SELECT sequence_name FROM sys.user_sequences';
    }

    public function getCreateTableSql($table, array $columns, array $options = array())
    {
        $indexes = isset($options['indexes']) ? $options['indexes'] : array();
        $options['indexes'] = array();
        $sql = parent::getCreateTableSql($table, $columns, $options);

        foreach ($columns as $name => $column) {
            if (isset($column['sequence'])) {
                $sql[] = $this->getCreateSequenceSql($column['sequence'], 1);
            }

            if (isset($column['autoincrement']) && $column['autoincrement'] ||
               (isset($column['autoinc']) && $column['autoinc'])) {           
                $sql = array_merge($sql, $this->getCreateAutoincrementSql($name, $table));
            }
        }
        
        if (isset($indexes) && ! empty($indexes)) {
            foreach ($indexes as $indexName => $definition) {
                // create nonunique indexes, as they are a part of CREATE TABLE DDL
                if ( ! isset($definition['type']) || 
                        (isset($definition['type']) && strtolower($definition['type']) != 'unique')) {
                    $sql[] = $this->getCreateIndexSql($table, $indexName, $definition);
                }
            }
        }

        return $sql;
    }

    /**
     *
     * @license New BSD License
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaOracleReader.html
     * @param  string $table
     * @return string
     */
    public function getListTableIndexesSql($table)
    {
        return "SELECT uind.index_name AS name, " .
             "       uind.index_type AS type, " .
             "       decode( uind.uniqueness, 'NONUNIQUE', 0, 'UNIQUE', 1 ) AS is_unique, " .
             "       uind_col.column_name AS column_name, " .
             "       uind_col.column_position AS column_pos " .
             "FROM user_indexes uind, user_ind_columns uind_col " .
             "WHERE uind.index_name = uind_col.index_name AND uind_col.table_name = '$table'";
    }

    public function getListTablesSql()
    {
        return 'SELECT * FROM sys.user_tables';
    }

    public function getListUsersSql()
    {
        return 'SELECT * FROM sys.dba_users';
    }

    public function getListViewsSql()
    {
        return 'SELECT view_name FROM sys.user_views';
    }

    public function getCreateViewSql($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSql($name)
    {
        return 'DROP VIEW '. $name;
    }

    public function getCreateAutoincrementSql($name, $table, $start = 1)
    {
        $table = strtoupper($table);
        $sql   = array();

        $indexName  = $table . '_AI_PK';
        $definition = array(
            'primary' => true,
            'columns' => array($name => true),
        );

        $sql[] = 'DECLARE
  constraints_Count NUMBER;
BEGIN
  SELECT COUNT(CONSTRAINT_NAME) INTO constraints_Count FROM USER_CONSTRAINTS WHERE TABLE_NAME = \''.$table.'\' AND CONSTRAINT_TYPE = \'P\';
  IF constraints_Count = 0 OR constraints_Count = \'\' THEN
    EXECUTE IMMEDIATE \''.$this->getCreateConstraintSql($table, $indexName, $definition).'\';
  END IF;
END;';   

        $sequenceName = $table . '_SEQ';
        $sql[] = $this->getCreateSequenceSql($sequenceName, $start);

        $triggerName  = $table . '_AI_PK';
        $sql[] = 'CREATE TRIGGER ' . $triggerName . '
   BEFORE INSERT
   ON ' . $table . '
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT ' . $sequenceName . '.NEXTVAL INTO :NEW.' . $name . ' FROM DUAL;
   IF (:NEW.' . $name . ' IS NULL OR :NEW.'.$name.' = 0) THEN
      SELECT ' . $sequenceName . '.NEXTVAL INTO :NEW.' . $name . ' FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE Sequence_Name = \'' . $sequenceName . '\';
      SELECT :NEW.' . $name . ' INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT ' . $sequenceName . '.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;';
        return $sql;
    }

    public function getDropAutoincrementSql($table)
    {
        $table = strtoupper($table);
        $trigger = $table . '_AI_PK';

        if ($trigger) {
            $sql[] = 'DROP TRIGGER ' . $trigger;
            $sql[] = $this->getDropSequenceSql($table.'_SEQ');

            $indexName = $table . '_AI_PK';
            $sql[] = $this->getDropConstraintSql($table, $indexName);
        }

        return $sql;
    }

    public function getListTableConstraintsSql($table)
    {
        $table = strtoupper($table);
        return 'SELECT * FROM user_constraints WHERE table_name = \'' . $table . '\'';
    }

    public function getListTableColumnsSql($table)
    {
        $table = strtoupper($table);
        return "SELECT * FROM all_tab_columns WHERE table_name = '" . $table . "' ORDER BY column_name";
    }

    public function getDropSequenceSql($sequenceName)
    {
        return 'DROP SEQUENCE ' . $sequenceName;
    }

    public function getDropDatabaseSql($database)
    {
        return 'DROP USER ' . $database . ' CASCADE';
    }

    /**
     * Gets the sql statements for altering an existing table.
     *
     * The method returns an array of sql statements, since some platforms need several statements.
     *
     * @param string $name          name of the table that is intended to be changed.
     * @param array $changes        associative array that contains the details of each type      *
     * @param boolean $check        indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is true or
     *                              actually perform them otherwise.
     * @return array
     */
    public function getAlterTableSql($name, array $changes, $check = false)
    {
        if ( ! $name) {
            throw DoctrineException::missingTableName();
        }
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'name':
                case 'rename':
                    break;
                default:
                    throw \Doctrine\Common\DoctrineException::alterTableChangeNotSupported($changeName);
            }
        }

        if ($check) {
            return false;
        }

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            $fields = array();
            foreach ($changes['add'] as $fieldName => $field) {
                $fields[] = $this->getColumnDeclarationSql($fieldName, $field);
            }
            $sql[] = 'ALTER TABLE ' . $name . ' ADD (' . implode(', ', $fields) . ')';
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $fieldName => $field) {
                $fields[] = $fieldName. ' ' . $this->getColumnDeclarationSql('', $field['definition']);
            }
            $sql[] = 'ALTER TABLE ' . $name . ' MODIFY (' . implode(', ', $fields) . ')';
        }

        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $sql[] = 'ALTER TABLE ' . $name . ' RENAME COLUMN ' . $fieldName
                       . ' TO ' . $field['name'];
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            $fields = array();
            foreach ($changes['remove'] as $fieldName => $field) {
                $fields[] = $fieldName;
            }
            $sql[] = 'ALTER TABLE ' . $name . ' DROP COLUMN ' . implode(', ', $fields);
        }

        if ( ! empty($changes['name'])) {
            $changeName = $changes['name'];
            $sql[] = 'ALTER TABLE ' . $name . ' RENAME TO ' . $changeName;
        }

        return $sql;
    }

    /**
     * Whether the platform prefers sequences for ID generation.
     *
     * @return boolean
     */
    public function prefersSequences()
    {
        return true;
    }

    /**
     * Get the platform name for this instance
     *
     * @return string
     */
    public function getName()
    {
        return 'oracle';
    }

    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query         query to modify
     * @param integer $limit        limit the number of rows
     * @param integer $offset       start reading from given offset
     * @return string               the modified query
     */
    public function modifyLimitQuery($query, $limit, $offset = null)
    {
        $limit = (int) $limit;
        $offset = (int) $offset;
        if (preg_match('/^\s*SELECT/i', $query)) {
            if ( ! preg_match('/\sFROM\s/i', $query)) {
                $query .= " FROM dual";
            }
            if ($limit > 0) {
                $max = $offset + $limit;
                $column = '*';
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
    }
    
    /**
     * Gets the character casing of a column in an SQL result set of this platform.
     * 
     * Oracle returns all column names in SQL result sets in uppercase.
     * 
     * @param string $column The column name for which to get the correct character casing.
     * @return string The column name in the character casing used in SQL result sets.
     */
    public function getSqlResultCasing($column)
    {
        return strtoupper($column);
    }
    
    public function getCreateTemporaryTableSnippetSql()
    {
        return "CREATE GLOBAL TEMPORARY TABLE";
    }
    
    public function getDateTimeFormatString()
    {
        return 'Y-m-d H:i:sP';
    }
    
    public function fixSchemaElementName($schemaElementName)
    {
        if (strlen($schemaElementName) > 30) {
            // Trim it
            return substr($schemaElementName, 0, 30);
        }
        return $schemaElementName;
    }
}