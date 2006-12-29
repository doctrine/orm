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
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_DataDict');
/**
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Sqlite extends Doctrine_DataDict
{
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
     */
    public function getNativeDeclaration(array $field)
    {
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
            if (!empty($field['length'])) {
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
            if (!empty($field['length'])) {
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
            return 'INTEGER';
        case 'date':
            return 'DATE';
        case 'time':
            return 'TIME';
        case 'timestamp':
            return 'DATETIME';
        case 'float':
        case 'double':
            return 'DOUBLE';//($db->options['fixed_float'] ? '('.
                //($db->options['fixed_float']+2).','.$db->options['fixed_float'].')' : '');
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            return 'DECIMAL('.$length.','.$db->options['decimal_places'].')';
        }
        throw new Doctrine_DataDict_Sqlite_Exception('Unknown datatype ' . $field['type']);
    }
    /**
     * Maps a native array description of a field to Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $length = ( ! empty($field['length'])) ? $field['length'] : null;
        $unsigned = ( ! empty($field['unsigned'])) ? $field['unsigned'] : null;
        $fixed = null;
        $type = array();
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
            throw new Doctrine_DataDict_Sqlite_Exception('unknown database attribute type: '.$dbType);
        }

        return array($type, $length, $unsigned, $fixed);
    }
    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param array  $field   associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    public function getIntegerDeclaration($name, array $field)
    {
        $default = $autoinc = '';
        $type    = $this->getNativeDeclaration($field);

        if (isset($field['autoincrement']) && $field['autoincrement']) {
            $autoinc = ' PRIMARY KEY AUTOINCREMENT';
            $type    = 'INTEGER';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT ' . $this->conn->quote($field['default'], $field['type']);
        }/**
        elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }
        */

        $notnull  = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';
        $unsigned = (isset($field['unsigned']) && $field['unsigned']) ? ' UNSIGNED' : '';

        $name = $this->conn->quoteIdentifier($name, true);
        return $name . ' ' . $type . $unsigned . $default . $notnull . $autoinc;
    }
}
