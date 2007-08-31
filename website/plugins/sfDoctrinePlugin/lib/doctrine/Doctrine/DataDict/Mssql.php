<?php
/*
 *  $Id: Mssql.php 1730 2007-06-18 18:27:11Z zYne $
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
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Frank M. Kromann <frank@kromann.info> (PEAR MDB2 Mssql driver)
 * @author      David Coallier <davidc@php.net> (PEAR MDB2 Mssql driver)
 * @version     $Revision: 1730 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Mssql extends Doctrine_DataDict
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
     *
     * @return      string      DBMS specific SQL code portion that should be used to
     *                          declare the specified field.
     */
    public function getNativeDeclaration($field)
    {
    	if ( ! isset($field['type'])) {
            throw new Doctrine_DataDict_Exception('Missing column type.');
    	}
        switch ($field['type']) {
            case 'array':
            case 'object':
            case 'text':
            case 'char':
            case 'varchar':
            case 'string':
            case 'gzip':
                $length = !empty($field['length'])
                    ? $field['length'] : false;

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$this->conn->options['default_text_field_length'].')')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return 'VARCHAR('.$length.')';
                    }
                 }
                 return 'TEXT';
            case 'blob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 8000) {
                        return "VARBINARY($length)";
                    }
                }
                return 'IMAGE';
            case 'integer':
            case 'enum':
            case 'int':
                return 'INT';
            case 'boolean':
                return 'BIT';
            case 'date':
                return 'CHAR(' . strlen('YYYY-MM-DD') . ')';
            case 'time':
                return 'CHAR(' . strlen('HH:MM:SS') . ')';
            case 'timestamp':
                return 'CHAR(' . strlen('YYYY-MM-DD HH:MM:SS') . ')';
            case 'float':
                return 'FLOAT';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale = !empty($field['scale']) ? $field['scale'] : $this->conn->getAttribute(Doctrine::ATTR_DECIMAL_PLACES);
                return 'DECIMAL('.$length.','.$scale.')';
        }

        throw new Doctrine_DataDict_Exception('Unknown field type \'' . $field['type'] .  '\'.');
    }
    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param   array           $field native field description
     * @return  array           containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration($field)
    {
        $db_type = preg_replace('/\d/','', strtolower($field['type']) );
        $length  = (isset($field['length']) && $field['length'] > 0) ? $field['length'] : null;

        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;

        if ( ! isset($field['name']))
            $field['name'] = '';

        switch ($db_type) {
            case 'bit':
                $type[0] = 'boolean';
            break;
            case 'int':
                $type[0] = 'integer';
                if ($length == 1) {
                    $type[] = 'boolean';
                }
            break;
            case 'datetime':
                $type[0] = 'timestamp';
            break;
            case 'float':
            case 'real':
            case 'numeric':
                $type[0] = 'float';
            break;
            case 'decimal':
            case 'money':
                $type[0] = 'decimal';
            break;
            case 'text':
            case 'varchar':
                $fixed = false;
            case 'char':
                $type[0] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^[is|has]/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($db_type, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'image':
            case 'varbinary':
                $type[] = 'blob';
                $length = null;
            break;
            default:
                throw new Doctrine_DataDict_Exception('unknown database attribute type: '.$db_type);
        }

        return array('type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed);
    }
}
