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
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    public function getTypeDeclaration($field) {
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
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $description = array();
        foreach ($result as $key => $val) {
            $description = array(
                'name'    => $val['field'],
                'type'    => $val['type'],
                'primary' => (strtolower($val['key']) == 'pri'),
                'default' => $val['default'],
                'notnull' => (bool) ($val['null'] != 'YES'),
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
        
        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
