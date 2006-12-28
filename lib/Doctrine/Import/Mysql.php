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
Doctrine::autoload('Doctrine_Import');
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
class Doctrine_Import_Mysql extends Doctrine_Import {
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
