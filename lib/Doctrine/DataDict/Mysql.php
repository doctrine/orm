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
class Doctrine_DataDict_Mysql extends Doctrine_DataDict {
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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    public function getNativeDeclaration($field) {
        switch ($field['type']) {
            case 'array':
            case 'object':
            case 'string':
                if (empty($field['length']) && array_key_exists('default', $field)) {
                    $field['length'] = $this->dbh->varchar_max_length;
                }
                
                $length = (! empty($field['length'])) ? $field['length'] : false;
                $fixed  = (! empty($field['fixed'])) ? $field['fixed'] : false;

                return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR(255)')
                    : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
            case 'clob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65532) {
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
                    } elseif ($length <= 65532) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'integer':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 1) {
                        return 'TINYINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3) {
                        return 'MEDIUMINT';
                    } elseif ($length == 4) {
                        return 'INT';
                    } elseif ($length > 4) {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case 'boolean':
                return 'TINYINT(1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
                return 'DOUBLE';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                return 'DECIMAL('.$length.','.$this->dbh->options['decimal_places'].')';
        }
        return '';
    }
    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getDoctrineDeclaration($field) {
        $db_type = strtolower($field['type']);
        $db_type = strtok($db_type, '(), ');
        if ($db_type == 'national') {
            $db_type = strtok('(), ');
        }
        if (!empty($field['length'])) {
            $length = $field['length'];
            $decimal = '';
        } else {
            $length = strtok('(), ');
            $decimal = strtok('(), ');
        }
        $type = array();
        $unsigned = $fixed = null;
        switch ($db_type) {
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
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
                $length = 4;
            break;
            case 'bigint':
                $type[] = 'integer';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
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
                $type[] = 'text';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($db_type, 'text')) {
                    $type[] = 'clob';
                    if ($decimal == 'binary') {
                        $type[] = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
            break;
            case 'enum':
                $type[] = 'text';
                preg_match_all('/\'.+\'/U', $field['type'], $matches);
                $length = 0;
                $fixed = false;
                if (is_array($matches)) {
                    foreach ($matches[0] as $value) {
                        $length = max($length, strlen($value)-2);
                    }
                    if ($length == '1' && count($matches[0]) == 2) {
                        $type[] = 'boolean';
                        if (preg_match('/^(is|has)/', $field['name'])) {
                            $type = array_reverse($type);
                        }
                    }
                }
                $type[] = 'integer';
            case 'set':
                $fixed = false;
                $type[] = 'text';
                $type[] = 'integer';
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
                $unsigned = preg_match('/ unsigned/i', $field['type']);
            break;
            case 'unknown':
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $unsigned = preg_match('/ unsigned/i', $field['type']);
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
                throw new Doctrine_DataDict_Mysql_Exception('unknown database attribute type: '.$db_type);
        }

        return array($type, $length, $unsigned, $fixed);
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $charset   name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration($charset) {
        return 'CHARACTER SET '.$charset;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param string $collation   name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation) {
        return 'COLLATE '.$collation;
    }
    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
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
     */
    public function getIntegerDeclaration($name, $field) {
        $default = $autoinc = '';
        if (!empty($field['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT PRIMARY KEY';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->conn->getDbh()->quote($field['default']);
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? '' : ' NOT NULL';
        $unsigned = empty($field['unsigned']) ? '' : ' UNSIGNED';

        $name = $this->conn->quoteIdentifier($name, true);

        return $name . ' ' . $this->getNativeDeclaration($field) . $unsigned . $default . $notnull . $autoinc;
    }
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases() {
        $sql = 'SHOW DATABASES';
        
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
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
    	$sql = 'select KCU.COLUMN_NAME as referencingColumn, TC.CONSTRAINT_NAME as constraintName, KCU.REFERENCED_TABLE_SCHEMA as referencedTableSchema, KCU.REFERENCED_TABLE_NAME as referencedTable, KCU.REFERENCED_COLUMN_NAME as referencedColumn from INFORMATION_SCHEMA.TABLE_CONSTRAINTS TC inner JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE  KCU on TC.CONSTRAINT_NAME=KCU.CONSTRAINT_NAME and TC.TABLE_SCHEMA = KCU.TABLE_SCHEMA and TC.TABLE_NAME=KCU.TABLE_NAME WHERE  TC.TABLE_SCHEMA=database() AND TC.TABLE_NAME="'.$table.'" AND CONSTRAINT_TYPE="FOREIGN KEY"';
	    
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table) { 
        $sql = "DESCRIBE $table";
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $description = array();
        foreach ($result as $key => $val2) {
	    $val = array();
	    foreach(array_keys($val2) as $valKey){ // lowercase the key names
		$val[strtolower($valKey)] = $val2[$valKey];
	    }
            $description = array(
                'name'    => $val['field'],
                'type'    => $val['type'],
                'primary' => (strtolower($val['key']) == 'pri'),
                'default' => $val['default'],
                'notnull' => (bool) ($val['null'] != 'YES'),
                'autoinc' => (bool) (strpos($val['extra'], 'auto_increment') > -1),
            );
            $columns[$val['field']] = new Doctrine_Schema_Column($description);
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
        $sql = 'SHOW TABLES';
        
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
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
