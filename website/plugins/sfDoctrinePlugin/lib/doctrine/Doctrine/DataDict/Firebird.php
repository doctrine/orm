<?php
/*
 *  $Id: Firebird.php 1731 2007-06-18 18:30:19Z zYne $
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
 * @author      Lorenzo Alberton <l.alberton@quipo.it> (PEAR MDB2 Interbase driver)
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision: 1731 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Firebird extends Doctrine_DataDict
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
    public function getNativeDeclaration($field)
    {
    	if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
    	}
        switch ($field['type']) {
            case 'varchar':
            case 'string':
            case 'array':
            case 'object':
            case 'char':
            case 'text':
            case 'gzip':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $this->conn->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR('.$length.')';
            case 'clob':
                return 'BLOB SUB_TYPE 1';
            case 'blob':
                return 'BLOB SUB_TYPE 0';
            case 'integer':
            case 'enum':
            case 'int':
                return 'INT';
            case 'boolean':
                return 'SMALLINT';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'TIMESTAMP';
            case 'float':
                return 'DOUBLE PRECISION';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }

        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }
    /**
     * Maps a native array description of a field to a Doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration($field)
    {
        $length  = (isset($field['length']) && $field['length'] > 0) ? $field['length'] : null;

        $type = array();
        $unsigned = $fixed = null;
        $dbType = strtolower($field['type']);
        $field['field_sub_type'] = !empty($field['field_sub_type'])
            ? strtolower($field['field_sub_type']) : null;

        if ( ! isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'smallint':
            case 'integer':
            case 'int64':
                //these may be 'numeric' or 'decimal'
                if (isset($field['field_sub_type'])) {
                    $field['type'] = $field['field_sub_type'];
                    return $this->getPortableDeclaration($field);
                }
            case 'bigint':
            case 'quad':
                $type[] = 'integer';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }
                break;
            case 'varchar':
                $fixed = false;
            case 'char':
            case 'cstring':
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
                $type[] = 'date';
                $length = null;
                break;
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
            case 'double precision':
            case 'd_float':
                $type[] = 'float';
                break;
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                break;
            case 'blob':
                $type[] = ($field['field_sub_type'] == 'text') ? 'clob' : 'blob';
                $length = null;
                break;
            default:
                throw new Doctrine_DataDict_Exception('unknown database attribute type: '.$dbType);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset)
    {
        return 'CHARACTER SET ' . $charset;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation)
    {
        return 'COLLATE ' . $collation;
    }
}
