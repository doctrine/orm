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
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;

class Db2Platform extends AbstractPlatform
{
    /**
     * Gets the SQL snippet used to declare a VARCHAR column type.
     *
     * @param array $field
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
                : ($length ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)');
    }

    /**
     * Gets the SQL snippet used to declare a CLOB column type.
     *
     * @param array $field
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        // todo clob(n) with $field['length'];
        return 'CLOB(1M)';
    }

    /**
     * Gets the name of the platform.
     *
     * @return string
     */
    public function getName()
    {
        return 'db2';
    }


    /**
     * Gets the SQL snippet that declares a boolean column.
     *
     * @param array $columnDef
     * @return string
     */
    public function getBooleanTypeDeclarationSQL(array $columnDef)
    {
        return 'SMALLINT';
    }

    /**
     * Gets the SQL snippet that declares a 4 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    public function getIntegerTypeDeclarationSQL(array $columnDef)
    {
        return 'INTEGER';
    }

    /**
     * Gets the SQL snippet that declares an 8 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    public function getBigIntTypeDeclarationSQL(array $columnDef)
    {
        return 'BIGINT';
    }

    /**
     * Gets the SQL snippet that declares a 2 byte integer column.
     *
     * @param array $columnDef
     * @return string
     */
    public function getSmallIntTypeDeclarationSQL(array $columnDef)
    {
        return 'SMALLINT';
    }

    /**
     * Gets the SQL snippet that declares common properties of an integer column.
     *
     * @param array $columnDef
     * @return string
     */
    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        
    }

    /**
     * Obtain DBMS specific SQL to be used to create datetime fields in
     * statements like CREATE TABLE
     *
     * @param array $fieldDeclaration
     * @return string
     */
    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIMESTAMP(0)';
    }

    /**
     * Obtain DBMS specific SQL to be used to create date fields in statements
     * like CREATE TABLE.
     *
     * @param array $fieldDeclaration
     * @return string
     */
    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    /**
     * Obtain DBMS specific SQL to be used to create time fields in statements
     * like CREATE TABLE.
     *
     * @param array $fieldDeclaration
     * @return string
     */
    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    public function getListDatabasesSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListSequencesSQL($database)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getListTableConstraintsSQL($table)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * This code fragment is originally from the Zend_Db_Adapter_Db2 class.
     *
     * @license New BSD License
     * @param  string $table
     * @return string
     */
    public function getListTableColumnsSQL($table)
    {
        return "SELECT DISTINCT c.tabschema, c.tabname, c.colname, c.colno,
                c.typename, c.default, c.nulls, c.length, c.scale,
                c.identity, tc.type AS tabconsttype, k.colseq
                FROM syscat.columns c
                LEFT JOIN (syscat.keycoluse k JOIN syscat.tabconst tc
                ON (k.tabschema = tc.tabschema
                    AND k.tabname = tc.tabname
                    AND tc.type = 'P'))
                ON (c.tabschema = k.tabschema
                    AND c.tabname = k.tabname
                    AND c.colname = k.colname)
                WHERE UPPER(c.tabname) = UPPER('" . $table . "') ORDER BY c.colno";
    }

    public function getListTablesSQL()
    {
        return "SELECT NAME FROM SYSIBM.SYSTABLES WHERE TYPE = 'T'";
    }

    public function getListUsersSQL()
    {
        throw DBALException::notSupported(__METHOD__);
    }

    /**
     * Get the SQL to list all views of a database or user.
     *
     * @param string $database
     * @return string
     */
    public function getListViewsSQL($database)
    {
        return "SELECT NAME, TEXT FROM SYSIBM.SYSVIEWS";
    }

    public function getListTableIndexesSQL($table)
    {
        return "SELECT NAME, COLNAMES, UNIQUERULE FROM SYSIBM.SYSINDEXES WHERE TBNAME = UPPER('" . $table . "')";
    }

    public function getListTableForeignKeysSQL($table)
    {
        return "SELECT TBNAME, RELNAME, REFTBNAME, DELETERULE, UPDATERULE, FKCOLNAMES, PKCOLNAMES ".
               "FROM SYSIBM.SYSRELS WHERE TBNAME = UPPER('".$table."')";
    }

    public function getCreateViewSQL($name, $sql)
    {
        return "CREATE VIEW ".$name." AS ".$sql;
    }

    public function getDropViewSQL($name)
    {
        return "DROP VIEW ".$name;
    }

    public function getDropSequenceSQL($sequence)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getSequenceNextValSQL($sequenceName)
    {
        throw DBALException::notSupported(__METHOD__);
    }

    public function getCreateDatabaseSQL($database)
    {
        return "CREATE DATABASE ".$database;
    }

    public function getDropDatabaseSQL($database)
    {
        return "DROP DATABASE ".$database.";";
    }

    public function supportsCreateDropDatabase()
    {
        return false;
    }

    /**
     * Gets the SQL specific for the platform to get the current date.
     *
     * @return string
     */
    public function getCurrentDateSQL()
    {
        return 'current date';
    }

    /**
     * Gets the SQL specific for the platform to get the current time.
     *
     * @return string
     */
    public function getCurrentTimeSQL()
    {
        return 'current time';
    }

    /**
     * Gets the SQL specific for the platform to get the current timestamp
     *
     * @return string
     */
    public function getCurrentTimestampSQL()
    {
        return 'current timestamp';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @param string $name          name of the index
     * @param Index $index          index definition
     * @return string               DBMS specific SQL code portion needed to set an index
     */
    public function getIndexDeclarationSQL($name, Index $index)
    {
        return $this->getUniqueConstraintDeclarationSQL($name, $index);
    }

    /**
     * @param string $tableName
     * @param array $columns
     * @param array $options
     * @return array
     */
    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $indexes = array();
        if (isset($options['indexes'])) {
            $indexes = $options['indexes'];
        }
        $options['indexes'] = array();
        
        $sqls = parent::_getCreateTableSQL($tableName, $columns, $options);

        foreach ($indexes as $index => $definition) {
            $sqls[] = $this->getCreateIndexSQL($definition, $tableName);
        }
        return $sqls;
    }

    /**
     * Gets the SQL to alter an existing table.
     *
     * @param TableDiff $diff
     * @return array
     */
    public function getAlterTableSQL(TableDiff $diff)
    {
        $sql = array();

        $queryParts = array();
        foreach ($diff->addedColumns AS $fieldName => $column) {
            $queryParts[] = 'ADD COLUMN ' . $this->getColumnDeclarationSQL($column->getName(), $column->toArray());
        }

        foreach ($diff->removedColumns AS $column) {
            $queryParts[] =  'DROP COLUMN ' . $column->getName();
        }

        foreach ($diff->changedColumns AS $columnDiff) {
            /* @var $columnDiff Doctrine\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;
            $queryParts[] =  'ALTER ' . ($columnDiff->oldColumnName) . ' '
                    . $this->getColumnDeclarationSQL($column->getName(), $column->toArray());
        }

        foreach ($diff->renamedColumns AS $oldColumnName => $column) {
            $queryParts[] =  'RENAME ' . $oldColumnName . ' TO ' . $column->getName();
        }

        if (count($queryParts) > 0) {
            $sql[] = 'ALTER TABLE ' . $diff->name . ' ' . implode(" ", $queryParts);
        }

        $sql = array_merge($sql, $this->_getAlterTableIndexForeignKeySQL($diff));

        if ($diff->newName !== false) {
            $sql[] =  'RENAME TABLE TO ' . $diff->newName;
        }

        return $sql;
    }

    public function getDefaultValueDeclarationSQL($field)
    {
        if (isset($field['notnull']) && $field['notnull'] && !isset($field['default'])) {
            if (in_array((string)$field['type'], array("Integer", "BigInteger", "SmallInteger"))) {
                $field['default'] = 0;
            } else {
                $field['default'] = '';
            }
        }

        return parent::getDefaultValueDeclarationSQL($field);
    }

    public function supportsIdentityColumns()
    {
        return true;
    }

    public function prefersIdentityColumns()
    {
        return true;
    }
}