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
 * @version     $Id$
 */
 
class Doctrine_DataDict_Mssql extends Doctrine_DataDict {
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

        $result  = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
                // need to add length to the type so we are compatible with
                // Zend_Db_Adapter_Pdo_Mysql!
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
