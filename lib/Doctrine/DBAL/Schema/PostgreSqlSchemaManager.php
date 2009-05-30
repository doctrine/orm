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
 * <http://www.phpdoctrine.org>.
 */

namespace Doctrine\DBAL\Schema;

/**
 * xxx
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @since       2.0
 */
class PostgreSqlSchemaManager extends AbstractSchemaManager
{
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        preg_match('/FOREIGN KEY \((.+)\) REFERENCES (.+)\((.+)\)/', $tableForeignKey['condef'], $values);

        if ((strpos(',', $values[1]) === false) && (strpos(',', $values[3]) === false)) {
            return array(
                'table'   => $values[2],
                'local'   => $values[1],
                'foreign' => $values[3]
            );
        }
    }

    protected function _getPortableTriggerDefinition($trigger)
    {
        return $trigger['trigger_name'];
    }

    protected function _getPortableViewDefinition($view)
    {
        return array(
            'name' => $view['viewname']
        );
    }

    protected function _getPortableUserDefinition($user)
    {
        return array(
            'user' => $user['usename'],
            'password' => $user['passwd']
        );
    }

    protected function _getPortableTableDefinition($table)
    {
        return $table['table_name'];
    }

    protected function _getPortableTableIndexDefinition($index)
    {
        return array(
            'name' => $index['relname'],
            'unique' => (bool) $index['unique']
        );
    }

    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['datname'];
    }

    protected function _getPortableSequenceDefinition($sequence)
    {
        return $sequence['relname'];
    }

    protected function _getPortableTableConstraintDefinition($tableConstraint)
    {
        return $tableConstraint['relname'];
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        if (strtolower($tableColumn['type']) === 'varchar') {
            // get length from varchar definition
            $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $tableColumn['complete_type']);
            $tableColumn['length'] = $length;
        }
        
        $matches = array(); 
        
        if (preg_match("/^nextval\('(.*)'(::.*)?\)$/", $tableColumn['default'], $matches)) {
            $tableColumn['sequence'] = $matches[1];
            $tableColumn['default'] = null;
        }
        
        if (stripos($tableColumn['default'], 'NULL') !== null) {
            $tableColumn['default'] = null;
        }
        
        $length = (isset($tableColumn['length'])) ? $tableColumn['length'] : null;
        if ($length == '-1' && isset($tableColumn['atttypmod'])) {
            $length = $tableColumn['atttypmod'] - 4;
        }
        if ((int)$length <= 0) {
            $length = null;
        }
        $type = array();
        $unsigned = $fixed = null;
        
        if ( ! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }
        
        $dbType = strtolower($tableColumn['type']);
        
        switch ($dbType) {
            case 'inet':
                $type = 'inet';
            break;
            case 'bit':
            case 'varbit':
                $type = 'bit';
            break;
            case 'smallint':
            case 'int2':
                $type = 'integer';
                $unsigned = false;
                $length = 2;
                if ($length == '2') {
                    if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                        $type = 'boolean';
                    }
                }
                break;
            case 'int':
            case 'int4':
            case 'integer':
            case 'serial':
            case 'serial4':
                $type = 'integer';
                $unsigned = false;
                $length = 4;
                break;
            case 'bigint':
            case 'int8':
            case 'bigserial':
            case 'serial8':
                $type = 'integer';
                $unsigned = false;
                $length = 8;
                break;
            case 'bool':
            case 'boolean':
                $type = 'boolean';
                $length = 1;
                break;
            case 'text':
            case 'varchar':
            case 'interval':
            case '_varchar':
                $fixed = false;
            case 'tsvector':
            case 'unknown':
            case 'char':
            case 'bpchar':
                $type = 'string';
                if ($length == '1') {
                    if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                        $type = 'boolean';
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
                $type = 'date';
                $length = null;
                break;
            case 'datetime':
            case 'timestamp':
            case 'timetz':
            case 'timestamptz':
                $type = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type = 'time';
                $length = null;
                break;
            case 'float':
            case 'float4':
            case 'float8':
            case 'double':
            case 'double precision':
            case 'real':
                $type = 'float';
                break;
            case 'decimal':
            case 'money':
            case 'numeric':
                $type = 'decimal';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'bytea':
            case 'geometry':
            case 'geometrycollection':
            case 'point':
            case 'multipoint':
            case 'linestring':
            case 'multilinestring':
            case 'polygon':
            case 'multipolygon':
                $type = 'blob';
                $length = null;
                break;
            case 'oid':
                $type = 'blob';
                $length = null;
                break;
            case 'year':
                $type = 'date';
                $length = null;
                break;
            default:
                $type = 'string';
        }

        $decl = array(
            'type'     => $type,
            'length'   => $length,
            'unsigned' => $unsigned,
            'fixed'    => $fixed
        );

        $description = array(
            'name'      => $tableColumn['field'],
            'notnull'   => (bool) $tableColumn['isnotnull'],
            'default'   => $tableColumn['default'],
            'primary'   => (bool) ($tableColumn['pri'] == 't'),
        );

        return array_merge($decl, $description);
    }
}