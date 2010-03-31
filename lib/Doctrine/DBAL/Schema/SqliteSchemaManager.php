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
    /**
     * {@inheritdoc}
     * 
     * @override
     */
    public function dropDatabase($database)
    {
        if (file_exists($database)) {
            unlink($database);
        }
    }

    /**
     * {@inheritdoc}
     * 
     * @override
     */
    public function createDatabase($database)
    {
        $params = $this->_conn->getParams();
        $driver = $params['driver'];
        $options = array(
            'driver' => $driver,
            'path' => $database
        );
        $conn = \Doctrine\DBAL\DriverManager::getConnection($options);
        $conn->connect();
        $conn->close();
    }

    protected function _getPortableTableDefinition($table)
    {
        return $table['name'];
    }

    /**
     * @license New BSD License
     * @link http://ezcomponents.org/docs/api/trunk/DatabaseSchema/ezcDbSchemaPgsqlReader.html
     * @param  array $tableIndexes
     * @param  string $tableName
     * @return array
     */
    protected function _getPortableTableIndexesList($tableIndexes, $tableName=null)
    {
        $indexBuffer = array();

        // fetch primary
        $stmt = $this->_conn->executeQuery( "PRAGMA TABLE_INFO ('$tableName')" );
        $indexArray = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach($indexArray AS $indexColumnRow) {
            if($indexColumnRow['pk'] == "1") {
                $indexBuffer[] = array(
                    'key_name' => 'primary',
                    'primary' => true,
                    'non_unique' => false,
                    'column_name' => $indexColumnRow['name']
                );
            }
        }

        // fetch regular indexes
        foreach($tableIndexes AS $tableIndex) {
            $keyName = $tableIndex['name'];
            $idx = array();
            $idx['key_name'] = $keyName;
            $idx['primary'] = false;
            $idx['non_unique'] = $tableIndex['unique']?false:true;

            $stmt = $this->_conn->executeQuery( "PRAGMA INDEX_INFO ( '{$keyName}' )" );
            $indexArray = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ( $indexArray as $indexColumnRow ) {
                $idx['column_name'] = $indexColumnRow['name'];
                $indexBuffer[] = $idx;
            }
        }

        return parent::_getPortableTableIndexesList($indexBuffer, $tableName);
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

        $precision = null;
        $scale = null;

        switch ($dbType) {
            case 'boolean':
                $type = 'boolean';
                break;
            case 'tinyint':
                $type = 'boolean';
                $length = null;
                break;
            case 'smallint':
                $type = 'smallint';
                $length = null;
                break;
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'serial':
                $type = 'integer';
                $length = null;
                break;
            case 'bigint':
            case 'bigserial':
                $type = 'bigint';
                $length = null;
                break;
            case 'clob':
                $fixed = false;
                $type = 'text';
                break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
                $type = 'text';
                break;
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
                $type = 'datetime';
                $length = null;
                break;
            case 'time':
                $type = 'time';
                $length = null;
                break;
            case 'float':
            case 'double':
            case 'real':
            case 'decimal':
            case 'numeric':
                list($precision, $scale) = array_map('trim', explode(', ', $tableColumn['length']));
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

        $options = array(
            'length'   => $length,
            'unsigned' => (bool) $unsigned,
            'fixed'    => $fixed,
            'notnull'  => $notnull,
            'default'  => $default,
            'precision' => $precision,
            'scale'     => $scale,
            'platformDetails' => array(
                'autoincrement' => (bool) $tableColumn['pk'],
            ),
        );

        return new Column($tableColumn['name'], \Doctrine\DBAL\Types\Type::getType($type), $options);
    }

    protected function _getPortableViewDefinition($view)
    {
        return new View($view['name'], $view['sql']);
    }
}