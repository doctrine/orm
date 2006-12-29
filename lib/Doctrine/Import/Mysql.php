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
class Doctrine_Import_Mysql extends Doctrine_Import
{
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        $sql = 'SHOW DATABASES';

        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
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
        $query = "SHOW TABLES";
        if (!is_null($database)) {
            $query .= " FROM $database";
        }
        $tableNames = $db->queryCol($query);

        $result = array();
        foreach ($tableNames as $tableName) {
            if ($sqn = $this->_fixSequenceName($tableName, true)) {
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
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $key_name = 'Key_name';
        $non_unique = 'Non_unique';
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $key_name = strtolower($key_name);
                $non_unique = strtolower($non_unique);
            } else {
                $key_name = strtoupper($key_name);
                $non_unique = strtoupper($non_unique);
            }
        }

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table";
        $indexes = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }

        $result = array();
        foreach ($indexes as $index_data) {
            if (!$index_data[$non_unique]) {
                if ($index_data[$key_name] !== 'PRIMARY') {
                    $index = $this->_fixIndexName($index_data[$key_name]);
                } else {
                    $index = 'PRIMARY';
                }
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
        $sql = "DESCRIBE $table";
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $description = array();
        foreach ($result as $key => $val2) {
	    $val = array();
	    foreach (array_keys($val2) as $valKey){ // lowercase the key names
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
    public function listTableIndexes($table)
    {
        $key_name = 'Key_name';
        $non_unique = 'Non_unique';
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $key_name = strtolower($key_name);
                $non_unique = strtolower($non_unique);
            } else {
                $key_name = strtoupper($key_name);
                $non_unique = strtoupper($non_unique);
            }
        }

        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table";
        $indexes = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);


        $result = array();
        foreach ($indexes as $index_data) {
            if ($index_data[$non_unique] && ($index = $this->_fixIndexName($index_data[$key_name]))) {
                $result[$index] = true;
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }
    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        $sql = 'SHOW TABLES';

        return $this->dbh->query($sql)->fetchAll(PDO::FETCH_COLUMN);
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
        return $db->queryCol('SELECT DISTINCT USER FROM USER');
    }
    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        $query = 'SHOW FULL TABLES';
        if (!is_null($database)) {
            $query.= ' FROM ' . $database;
        }
        $query.= " WHERE Table_type = 'VIEW'";

        $result = $db->queryCol($query);

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
}
