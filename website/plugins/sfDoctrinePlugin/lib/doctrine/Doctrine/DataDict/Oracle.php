<?php
/*
 *  $Id: Oracle.php 1334 2007-05-11 19:20:38Z lsmith $
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
 * @subpackage  Doctrine_DataDict
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version     $Revision: 1334 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Oracle extends Doctrine_DataDict
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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getNativeDeclaration(array $field)
    {
    	if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
    	}
        switch ($field['type']) {
            case 'string':
            case 'array':
            case 'object':
            case 'gzip':
            case 'char':
            case 'varchar':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $this->conn->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR2('.$length.')';
            case 'clob':
                return 'CLOB';
            case 'blob':
                return 'BLOB';
            case 'integer':
            case 'enum':
            case 'int':
                if (!empty($field['length'])) {
                    return 'NUMBER('.$field['length'].')';
                }
                return 'INT';
            case 'boolean':
                return 'NUMBER(1)';
            case 'date':
            case 'time':
            case 'timestamp':
                return 'DATE';
            case 'float':
            case 'double':
                return 'NUMBER';
            case 'decimal':
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'NUMBER(*,'.$scale.')';
            default:
        }
        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }
    /**
     * Maps a native array description of a field to a doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @throws Doctrine_DataDict_Oracle_Exception
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $type = array();
        $length = $unsigned = $fixed = null;
        if (!empty($field['length'])) {
            $length = $field['length'];
        }

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'integer':
            case 'pls_integer':
            case 'binary_integer':
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'varchar':
            case 'varchar2':
            case 'nvarchar2':
                $fixed = false;
            case 'char':
            case 'nchar':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'date':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'float':
                $type[] = 'float';
                break;
            case 'number':
                if (!empty($field['scale'])) {
                    $type[] = 'decimal';
                } else {
                    $type[] = 'integer';
                    if ($length == '1') {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['name'])) {
                            $type = array_reverse($type);
                        }
                    }
                }
                break;
            case 'long':
                $type[] = 'string';
            case 'clob':
            case 'nclob':
                $type[] = 'clob';
                break;
            case 'blob':
            case 'raw':
            case 'long raw':
            case 'bfile':
                $type[] = 'blob';
                $length = null;
            break;
            case 'rowid':
            case 'urowid':
            default:
                throw new Doctrine_DataDict_Exception('unknown database attribute type: ' . $dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
}
