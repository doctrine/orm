<?php
/*
 *  $Id: Sqlite.php 1889 2007-06-28 12:11:55Z zYne $
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
 * @version     $Revision: 1889 $
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
        $query      = "SELECT name FROM sqlite_master WHERE type='table' AND sql NOT NULL ORDER BY name";
        $tableNames = $this->conn->fetchColumn($query);

        $result = array();
        foreach ($tableNames as $tableName) {
            if ($sqn = $this->conn->fixSequenceName($tableName, true)) {
                $result[] = $sqn;
            }
        }
        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_map(($this->conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
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
        $table = $this->conn->quote($table, 'text');

        $query = "SELECT sql FROM sqlite_master WHERE type='index' AND ";

        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $query .= 'LOWER(tbl_name) = ' . strtolower($table);
        } else {
            $query .= 'tbl_name = ' . $table;
        }
        $query  .= ' AND sql NOT NULL ORDER BY name';
        $indexes = $this->conn->fetchColumn($query);

        $result = array();
        foreach ($indexes as $sql) {
            if (preg_match("/^create unique index ([^ ]+) on /i", $sql, $tmp)) {
                $index = $this->conn->fixIndexName($tmp[1]);
                if ( ! empty($index)) {
                    $result[$index] = true;
                }
            }
        }

        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $this->conn->getAttribute(Doctrine::ATTR_FIELD_CASE));
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
        $result = $this->conn->fetchAll($sql);

        $description = array();
        $columns     = array();
        foreach ($result as $key => $val) {
            $val = array_change_key_case($val, CASE_LOWER);
            $decl = $this->conn->dataDict->getPortableDeclaration($val);

            $description = array(
                    'name'      => $val['name'],
                    'ntype'     => $val['type'],
                    'type'      => $decl['type'][0],
                    'alltypes'  => $decl['type'],
                    'notnull'   => (bool) $val['notnull'],
                    'default'   => $val['dflt_value'],
                    'primary'   => (bool) $val['pk'],
                    'length'    => null,
                    'scale'     => null,
                    'precision' => null,
                    'unsigned'  => null,
                    );
            $columns[$val['name']] = $description;
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
        $sql  = 'PRAGMA index_list(' . $table . ')';
        return $this->conn->fetchColumn($sql);
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

        return $this->conn->fetchColumn($sql);
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
        $query = "SELECT name, sql FROM sqlite_master WHERE type='view' AND sql NOT NULL";
        $views = $db->fetchAll($query);

        $result = array();
        foreach ($views as $row) {
            if (preg_match("/^create view .* \bfrom\b\s+\b{$table}\b /i", $row['sql'])) {
                if ( ! empty($row['name'])) {
                    $result[$row['name']] = true;
                }
            }
        }
        return $result;
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
        $query = "SELECT name FROM sqlite_master WHERE type='view' AND sql NOT NULL";

        return $this->conn->fetchColumn($query);
    }
}

