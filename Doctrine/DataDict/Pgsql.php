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
 
class Doctrine_DataDict_Mysql extends Doctrine_DataDict {
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
        $sql = "SELECT a.attnum, a.attname AS field, t.typname AS type, format_type(a.atttypid, a.atttypmod) AS complete_type, "
             . "a.attnotnull AS isnotnull, "
             . "( SELECT 't' "
             . "FROM pg_index "
             . "WHERE c.oid = pg_index.indrelid "
             . "AND pg_index.indkey[0] = a.attnum "
             . "AND pg_index.indisprimary = 't') AS pri, "
             . "(SELECT pg_attrdef.adsrc "
             . "FROM pg_attrdef "
             . "WHERE c.oid = pg_attrdef.adrelid "
             . "AND pg_attrdef.adnum=a.attnum) AS default "
             . "FROM pg_attribute a, pg_class c, pg_type t "
             . "WHERE c.relname = '" . $table . "' "
             . "AND a.attnum > 0 "
             . "AND a.attrelid = c.oid "
             . "AND a.atttypid = t.oid "
             . "ORDER BY a.attnum ";
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $columns     = array();
        foreach ($result as $key => $val) {
            if ($val['type'] === 'varchar') {
                // need to add length to the type so we are compatible with
                // Zend_Db_Adapter_Pdo_Pgsql!
                $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $val['complete_type']);
                $val['type'] .= '(' . $length . ')';
            }
            $description = array(
                'name'    => $val['field'],
                'type'    => $val['type'],
                'notnull' => ($val['isnotnull'] == ''),
                'default' => $val['default'],
                'primary' => ($val['pri'] == 't'),
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
        $sql = "SELECT c.relname AS table_name "
             . "FROM pg_class c, pg_user u "
             . "WHERE c.relowner = u.usesysid AND c.relkind = 'r' "
             . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
             . "AND c.relname !~ '^(pg_|sql_)' "
             . "UNION "
             . "SELECT c.relname AS table_name "
             . "FROM pg_class c "
             . "WHERE c.relkind = 'r' "
             . "AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname) "
             . "AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner) "
             . "AND c.relname !~ '^pg_'";
        
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
