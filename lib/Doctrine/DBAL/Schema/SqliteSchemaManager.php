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
 * SqliteSchemaManager
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @version     $Revision$
 * @since       2.0
 */
class SqliteSchemaManager extends AbstractSchemaManager
{
    public function dropDatabase($database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDriver()->getDatabase($this->_conn);
        }
        unlink($database);
    }

    public function createDatabase($database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDriver()->getDatabase($this->_conn);
        }
        // TODO: Can we do this better?
        $this->_conn->close();
        $this->_conn->connect();
    }

    protected function _getPortableTableDefinition($table)
    {
        return $table['name'];
    }

    protected function _getPortableTableIndexDefinition($tableIndex)
    {
        return array(
            'name' => $tableIndex['name'],
            'unique' => (bool) $tableIndex['unique']
        );
    }

    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $e = explode('(', $tableColumn['type']);
        $tableColumn['type'] = $e[0];
        if (isset($e[1])) {
            $length = trim($e[1], ')');
            $tableColumn['length'] = $length;
        }

        $dbType = strtolower($tableColumn['type']);

        $length = isset($tableColumn['length']) ? $tableColumn['length'] : null;
        $unsigned = (boolean) isset($tableColumn['unsigned']) ? $tableColumn['unsigned'] : false;
        $fixed = false;
        $type = null;
        $default = $tableColumn['dflt_value'];
        if  ($default == 'NULL') {
            $default = null;
        }
        $notnull = (bool) $tableColumn['notnull'];

        if ( ! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        switch ($dbType) {
            case 'boolean':
                $type = 'boolean';
                break;
            case 'tinyint':
                if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                    $type = 'boolean';
                } else {
                    $type = 'integer';
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
            case 'serial':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 4;
                break;
            case 'bigint':
            case 'bigserial':
                $type = 'integer';
                $unsigned = preg_match('/ unsigned/i', $tableColumn['type']);
                $length = 8;
                break;
            case 'clob':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'varchar':
            case 'varchar2':
            case 'nvarchar':
            case 'ntext':
            case 'image':
            case 'nchar':
                $fixed = false;
            case 'char':
                $type = 'string';
                if ($length == '1') {
                    $type = 'boolean';
                    if (preg_match('/^(is|has)/', $tableColumn['name'])) {
                        $type = array_reverse($type);
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
                $length = null;
                break;
            case 'decimal':
            case 'numeric':
                $type = 'decimal';
                $length = null;
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type = 'blob';
                $length = null;
                break;
            case 'year':
                $type = 'date';
                $length = null;
                break;
            default:
                $type = 'string';
                $length = null;
        }

        return array('name'     => $tableColumn['name'],
                     'primary'  => (bool) $tableColumn['pk'],
                     'type'     => $type,
                     'length'   => $length,
                     'unsigned' => (bool) $unsigned,
                     'fixed'    => $fixed,
                     'notnull'  => $notnull,
                     'default'  => $default);
    }
}