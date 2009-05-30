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
class MySqlSchemaManager extends AbstractSchemaManager
{
    protected function _getPortableViewDefinition($view)
    {
        return $view['table_name'];
    }

    protected function _getPortableTableDefinition($table)
    {
        return end($table);
    }

    protected function _getPortableUserDefinition($user)
    {
        return array(
            'user' => $user['user'],
            'password' => $user['password'],
        );
    }

    protected function _getPortableTableIndexDefinition($tableIndex)
    {
        $tableIndex = array_change_key_case($tableIndex, CASE_LOWER);

        $result = array();
        if ($tableIndex['key_name'] != 'PRIMARY' && ($index = $tableIndex['key_name'])) {
            $result['name'] = $index;
            $result['column'] = $tableIndex['column_name'];
            $result['unique'] = $tableIndex['non_unique'] ? false : true;
        }

        return $result;
    }

    protected function _getPortableTableConstraintDefinition($tableConstraint)
    {
        $tableConstraint = array_change_key_case($tableConstraint, CASE_LOWER);

        if ( ! $tableConstraint['non_unique']) {
            $index = $tableConstraint['key_name'];
            if ( ! empty($index)) {
                return $index;
            }
        }
    }

    protected function _getPortableSequenceDefinition($sequence)
    {
        return end($sequence);
    }

    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['database'];
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');
        if ($dbType == 'national') {
            $dbType = strtok('(), ');
        }
        if (isset($tableColumn['length'])) {
            $length = $tableColumn['length'];
            $decimal = '';
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ') ? strtok('(), '):null;
        }
        $type = array();
        $unsigned = $fixed = null;

        if ( ! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $values = null;
        $scale = null;

        switch ($dbType) {
            case 'tinyint':
                $type = 'integer';
                $type = 'boolean';
                if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 1;
            break;
            case 'smallint':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 2;
            break;
            case 'mediumint':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 3;
            break;
            case 'int':
            case 'integer':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 4;
            break;
            case 'bigint':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 8;
            break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'string':
            case 'char':
                $type = 'string';
                if ($length == '1') {
                    $type = 'boolean';
                    if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type = 'clob';
                    if ($decimal == 'binary') {
                        $type = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'enum':
                $type = 'enum';
                preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $tableColumn['type'], $matches);
                $length = 0;
                $fixed = false;
                if (is_array($matches)) {
                    foreach ($matches[1] as &$value) {
                        $value = str_replace('\'\'', '\'', $value);
                        $length = max($length, strlen($value));
                    }
                    if ($length == '1' && count($matches[1]) == 2) {
                        $type = 'boolean';
                        if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                            $type = array_reverse($type);
                        }
                    }

                    $values = $matches[1];
                }
                $type = 'integer';
                break;
            case 'set':
                $fixed = false;
                $type = 'text';
                $type = 'integer';
            break;
            case 'date':
                $type = 'date';
                $length = null;
            break;
            case 'datetime':
            case 'timestamp':
                $type = 'timestamp';
                $length = null;
            break;
            case 'time':
                $type = 'time';
                $length = null;
            break;
            case 'float':
            case 'double':
            case 'real':
                $type = 'float';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
            break;
            case 'unknown':
            case 'decimal':
                if ($decimal !== null) {
                    $scale = $decimal;
                }
            case 'numeric':
                $type = 'decimal';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
            break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'binary':
            case 'varbinary':
                $type = 'blob';
                $length = null;
            break;
            case 'year':
                $type = 'integer';
                $type = 'date';
                $length = null;
            break;
            case 'bit':
                $type = 'bit';
            break;
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
            default:
                $type = 'string';
                $length = null;
        }

        $length = ((int) $length == 0) ? null : (int) $length;
        $def =  array(
            'type' => $type,
            'length' => $length,
            'unsigned' => (bool) $unsigned,
            'fixed' => (bool) $fixed
        );
        if ($values !== null) {
            $def['values'] = $values;
        }
        if ($scale !== null) {
            $def['scale'] = $scale;
        }

        $values = isset($def['values']) ? $def['values'] : array();

        $column = array(
            'name'          => $tableColumn['field'],
            'values'        => $values,
            'primary'       => (bool) (strtolower($tableColumn['key']) == 'pri'),
            'default'       => $tableColumn['default'],
            'notnull'       => (bool) ($tableColumn['null'] != 'YES'),
            'autoincrement' => (bool) (strpos($tableColumn['extra'], 'auto_increment') !== false),
        );

        $column = array_merge($column, $def);

        return $column;
    }

    public function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        $tableForeignKey = array_change_key_case($tableForeignKey, CASE_LOWER);
        $foreignKey = array(
            'table'   => $tableForeignKey['referenced_table_name'],
            'local'   => $tableForeignKey['column_name'],
            'foreign' => $tableForeignKey['referenced_column_name']
        );
        return $foreignKey;
    }

    public function createSequence($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->_conn->quoteIdentifier($this->_conn->getSequenceName($sequenceName), true);
        $seqcolName     = $this->_conn->quoteIdentifier($this->_conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $optionsStrings = array();

        if (isset($options['comment']) && ! empty($options['comment'])) {
            $optionsStrings['comment'] = 'COMMENT = ' . $this->_conn->quote($options['comment'], 'string');
        }

        if (isset($options['charset']) && ! empty($options['charset'])) {
            $optionsStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];

            if (isset($options['collate'])) {
                $optionsStrings['collate'] .= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->_conn->default_table_type;
        }
        if ($type) {
            $optionsStrings[] = 'ENGINE = ' . $type;
        }

        try {
            $query  = 'CREATE TABLE ' . $sequenceName
                    . ' (' . $seqcolName . ' INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                    . $seqcolName . '))';

            if (!empty($options_strings)) {
                $query .= ' '.implode(' ', $options_strings);
            }

            $res    = $this->_conn->exec($query);
        } catch(Doctrine\DBAL\ConnectionException $e) {
            throw \Doctrine\Common\DoctrineException::updateMe('could not create sequence table');
        }

        if ($start == 1) {
            return true;
       }

        $query  = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')';

        $res    = $this->_conn->exec($query);

      // Handle error
      try {
          $res = $this->_conn->exec('DROP TABLE ' . $sequenceName);
      } catch(Doctrine\DBAL\ConnectionException $e) {
          throw \Doctrine\Common\DoctrineException::updateMe('could not drop inconsistent sequence table');
      }

      return $res;
    }
}