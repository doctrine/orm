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
class Doctrine_Import_Sqlite extends Doctrine_Import
{
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {

    }
    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions()
    {

    }
    /**
     * lists all database triggers
     *
     * @param string|null $database
     * @return array
     */
    public function listTriggers($database = null)
    {

    }
    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            if ($sqn = $this->_fixSequenceName($table_name, true)) {
                $result[] = $sqn;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $table = $db->quote($table, 'text');
        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $query.= 'LOWER(tbl_name)='.strtolower($table);
        } else {
            $query.= "tbl_name=$table";
        }
        $query.= " AND sql NOT NULL ORDER BY name";
        $indexes = $db->queryCol($query, 'text');
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create unique index ([^ ]+) on /i", $sql, $tmp)) {
                $index = $this->_fixIndexName($tmp[1]);
                if (!empty($index)) {
                    $result[$index] = true;
                }
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {

        $sql    = 'PRAGMA table_info(' . $table . ')';
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $description = array();
        $columns     = array();
        foreach ($result as $key => $val) {
            $description = array(
                    'name'    => $val['name'],
                    'type'    => $val['type'],
                    'notnull' => (bool) $val['notnull'],
                    'default' => $val['dflt_value'],
                    'primary' => (bool) $val['pk'],
                    );
            $columns[$val['name']] = new Doctrine_Schema_Column($description);
        }
        return $columns;
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        $sql     =  'PRAGMA index_list(' . $table . ')';
        $result  = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $indexes = array();
        foreach ($result as $key => $val) {

        }
    }
    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        $sql = "SELECT name FROM sqlite_master WHERE type = 'table' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type = 'table' ORDER BY name";

        $tables = array();
        $stmt   = $this->dbh->query($sql);

        $data   = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($data as $table) {
            $tables[] = new Doctrine_Schema_Table(array('name' => $table));
        }
        return $tables;
    }
    /**
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table)
    {

    }
    /**
     * lists table views
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {

    }
    /**
     * lists database users
     *
     * @return array
     */
    public function listUsers()
    {

    }
    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {

    }
}

