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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_DataDict_Oracle extends Doctrine_DataDict {
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
    public function getNativeDeclaration(array $field) {
        switch ($field['type']) {
            case 'string':
            case 'array':
            case 'object':
            case 'gzip':
            case 'char':
            case 'varchar':
                $length = !empty($field['length'])
                    ? $field['length'] : 16777215; // TODO: $db->options['default_text_field_length'];

                $fixed  = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;
                
                return $fixed ? 'CHAR('.$length.')' : 'VARCHAR2('.$length.')';
            case 'clob':
                return 'CLOB';
            case 'blob':
                return 'BLOB';
            case 'integer':
            case 'enum':
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
                return 'NUMBER';
            case 'decimal':
                return 'NUMBER(*,'.$db->options['decimal_places'].')';
        }
    }
    /**
     * Maps a native array description of a field to a doctrine datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @throws Doctrine_DataDict_Oracle_Exception
     */
    public function getPortableDeclaration(array $field) {
        $db_type = strtolower($field['type']);
        $type = array();
        $length = $unsigned = $fixed = null;
        if (!empty($field['length'])) {
            $length = $field['length'];
        }
        switch ($db_type) {
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
                $type[] = 'text';
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
                $type[] = 'text';
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
                throw new Doctrine_DataDict_Oracle_Exception('unknown database attribute type: '.$db_type);
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
        $sql = "DESCRIBE $table";
        $result  = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $columns = array();
        foreach ($result as $key => $val) {
            $description = array(
                'name'    => $val['Field'],
                'notnull' => (bool) ($val['Null'] === ''),
                'type'    => $val['Type'],
            );
            $columns[$val['Field']] = new Doctrine_Schema_Column($description);
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
		return $this->dbh->fetchCol('SELECT table_name FROM all_tables ORDER BY table_name');
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
