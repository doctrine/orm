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

namespace Doctrine\DBAL\Schema;

/**
 * IBM Db2 Schema Manager
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @version     $Revision$
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 */
class DB2SchemaManager extends AbstractSchemaManager
{
    /**
     * Return a list of all tables in the current database
     *
     * Apparently creator is the schema not the user who created it:
     * {@link http://publib.boulder.ibm.com/infocenter/dzichelp/v2r2/index.jsp?topic=/com.ibm.db29.doc.sqlref/db2z_sysibmsystablestable.htm}
     *
     * @return array
     */
    public function listTableNames()
    {
        $sql = $this->_platform->getListTablesSQL();
        $sql .= " AND CREATOR = UPPER('".$this->_conn->getUsername()."')";

        $tables = $this->_conn->fetchAll($sql);
        
        return $this->_getPortableTablesList($tables);
    }


    /**
     * Get Table Column Definition
     *
     * @param array $tableColumn
     * @return Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, \CASE_LOWER);

        $length = null;
        $fixed = null;
        $unsigned = false;
        $scale = false;
        $precision = false;
        
        switch (strtolower($tableColumn['typename'])) {
            case 'smallint':
            case 'bigint':
            case 'integer':
            case 'time':
            case 'date':
                $type = strtolower($tableColumn['typename']);
                break;
            case 'varchar':
                $type = 'string';
                $length = $tableColumn['length'];
                $fixed = false;
                break;
            case 'character':
                $type = 'string';
                $length = $tableColumn['length'];
                $fixed = true;
                break;
            case 'clob':
                $type = 'text';
                $length = $tableColumn['length'];
                break;
            case 'decimal':
            case 'double':
            case 'real':
                $type = 'decimal';
                $scale = $tableColumn['scale'];
                $precision = $tableColumn['length'];
                break;
            case 'timestamp':
                $type = 'datetime';
                break;
            default:
                throw new \Doctrine\DBAL\DBALException("Unknown Type: ".$tableColumn['typename']);
        }

        $options = array(
            'length'        => $length,
            'unsigned'      => (bool)$unsigned,
            'fixed'         => (bool)$fixed,
            'default'       => ($tableColumn['default'] == "NULL") ? null : $tableColumn['default'],
            'notnull'       => (bool) ($tableColumn['nulls'] == 'N'),
            'scale'         => null,
            'precision'     => null,
            'platformOptions' => array(),
        );

        if ($scale !== null && $precision !== null) {
            $options['scale'] = $scale;
            $options['precision'] = $precision;
        }

        return new Column($tableColumn['colname'], \Doctrine\DBAL\Types\Type::getType($type), $options);
    }

    protected function _getPortableTablesList($tables)
    {
        $tableNames = array();
        foreach ($tables AS $tableRow) {
            $tableRow = array_change_key_case($tableRow, \CASE_LOWER);
            $tableNames[] = $tableRow['name'];
        }
        return $tableNames;
    }

    protected function _getPortableTableIndexesList($tableIndexes, $tableName=null)
    {
        $tableIndexRows = array();
        $indexes = array();
        foreach($tableIndexes AS $indexKey => $data) {
            $data = array_change_key_case($data, \CASE_LOWER);
            $unique = ($data['uniquerule'] == "D") ? false : true;
            $primary = ($data['uniquerule'] == "P");

            $indexName = strtolower($data['name']);
            if ($primary) {
                $keyName = 'primary';
            } else {
                $keyName = $indexName;
            }

            $indexes[$keyName] = new Index($indexName, explode("+", ltrim($data['colnames'], '+')), $unique, $primary);
        }

        return $indexes;
    }

    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        $tableForeignKey = array_change_key_case($tableForeignKey, CASE_LOWER);

        $tableForeignKey['deleterule'] = $this->_getPortableForeignKeyRuleDef($tableForeignKey['deleterule']);
        $tableForeignKey['updaterule'] = $this->_getPortableForeignKeyRuleDef($tableForeignKey['updaterule']);

        return new ForeignKeyConstraint(
            array_map('trim', (array)$tableForeignKey['fkcolnames']),
            $tableForeignKey['reftbname'],
            array_map('trim', (array)$tableForeignKey['pkcolnames']),
            $tableForeignKey['relname'],
            array(
                'onUpdate' => $tableForeignKey['updaterule'],
                'onDelete' => $tableForeignKey['deleterule'],
            )
        );
    }

    protected function _getPortableForeignKeyRuleDef($def)
    {
        if ($def == "C") {
            return "CASCADE";
        } else if ($def == "N") {
            return "SET NULL";
        }
        return null;
    }

    protected function _getPortableViewDefinition($view)
    {
        $view = array_change_key_case($view, \CASE_LOWER);
        // sadly this still segfaults on PDO_IBM, see http://pecl.php.net/bugs/bug.php?id=17199
        //$view['text'] = (is_resource($view['text']) ? stream_get_contents($view['text']) : $view['text']);
        if (!is_resource($view['text'])) {
            $pos = strpos($view['text'], ' AS ');
            $sql = substr($view['text'], $pos+4);
        } else {
            $sql = '';
        }

        return new View($view['name'], $sql);
    }
}