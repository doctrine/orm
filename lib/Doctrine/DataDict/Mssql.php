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

/**
 * @package     Doctrine
 * @url         http://www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Id$
 */
 
class Doctrine_DataDict_Mssql extends Doctrine_DataDict {
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
     * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
     * @return      string      DBMS specific SQL code portion that should be used to
     *                          declare the specified field.
     */
    public function getTypeDeclaration($field) {
        switch ($field['type']) {
        case 'text':
            $length = !empty($field['length'])
                ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
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
            return 'INT';
        case 'boolean':
            return 'BIT';
        case 'date':
            return 'CHAR ('.strlen('YYYY-MM-DD').')';
        case 'time':
            return 'CHAR ('.strlen('HH:MM:SS').')';
        case 'timestamp':
            return 'CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')';
        case 'float':
            return 'FLOAT';
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            return 'DECIMAL('.$length.','.$db->options['decimal_places'].')';
        }
        return '';
    }
    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param   array           $field native field description
     * @author  Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
     * @return  array           containing the various possible types, length, sign, fixed
     */
    public function mapNativeDatatype($field) {
        $db_type = preg_replace('/\d/','', strtolower($field['type']) );
        $length = $field['length'];
        if ((int)$length <= 0) {
            $length = null;
        }
        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;
        switch ($db_type) {
            case 'bit':
                $type[0] = 'boolean';
            break;
            case 'int':
                $type[0] = 'integer';
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
                $type[0] = 'text';
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
                throw new Doctrine_DataDict_Mssql_Exception('mapNativeDatatype: unknown database attribute type: '.$db_type);
        }

        return array($type, $length, $unsigned, $fixed);
    }
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases() {

    }
    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions() {
    
    }
    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null) {

    }
    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null) { 
    
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table) {
    
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table) { 
        $sql     = "exec sp_columns @table_name = " . $this->quoteIdentifier($table);
        $result  = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $columns = array();

        foreach ($result as $key => $val) {
            if (strstr($val['type_name'], ' ')) {
                list($type, $identity) = explode(' ', $val['type_name']);
            } else {
                $type = $val['type_name'];
                $identity = '';
            }

            if ($type == 'varchar') {
                $type .= '('.$val['length'].')';
            }

            $description  = array(
                'name'    => $val['column_name'],
                'type'    => $type,
                'notnull' => (bool) ($val['is_nullable'] === 'NO'),
                'default' => $val['column_def'],
                'primary' => (strtolower($identity) == 'identity'),
            );
            $columns[$val['column_name']] = new Doctrine_Schema_Column($description);
        }

        return $columns;
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table) {
    
    }
    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null) {
        $sql = "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";

        return $this->dbh->fetchCol($sql);
    }
    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table) { 
    
    }
    /**
     * lists table views
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table) { 
    
    }
    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers() { 
    
    }
    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null) { 
    
    }
}
