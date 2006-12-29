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
 * @version     $Revision$
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Import_Oracle extends Doctrine_Import
{
    /**
     * lists all databases
     *
     * @return array
     */
    public function listDatabases()
    {
        if ( ! $this->conn->options['emulate_database']) {
            return $this->conn->raiseError(Doctrine::ERROR_UNSUPPORTED, null, null,
                'database listing is only supported if the "emulate_database" option is enabled', __FUNCTION__);
        }

        if ($this->conn->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($this->conn->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username LIKE '";
            $query.= $this->conn->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        $result2 = $this->conn->standaloneQuery($query, array('text'), false);
        $result  = $result2->fetchCol();

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        $result2->free();
        return $result;
    }
    /**
     * lists all availible database functions
     *
     * @return array
     */
    public function listFunctions()
    {
        $query = "SELECT name FROM sys.user_source WHERE line = 1 AND type = 'FUNCTION'";
        $result = $this->conn->queryCol($query);

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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
        $query = "SELECT sequence_name FROM sys.user_sequences";
        $tableNames = $this->conn->queryCol($query);

        $result = array();
        foreach ($tableNames as $tableName) {
            $result[] = $this->_fixSequenceName($tableName);
        }
        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
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
        $query = 'SELECT index_name name FROM user_constraints';
        $query.= ' WHERE table_name='.$table.' OR table_name='.strtoupper($table);
        $constraints = $this->conn->queryCol($query);

        $result = array();
        foreach ($constraints as $constraint) {
            $constraint = $this->_fixIndexName($constraint);
            if (!empty($constraint)) {
                $result[$constraint] = true;
            }
        }

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_change_key_case($result, $this->conn->options['field_case']);
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

        $table = $this->conn->quote($table, 'text');
        $query = 'SELECT column_name FROM user_tab_columns';
        $query.= ' WHERE table_name='.$table.' OR table_name='.strtoupper($table).' ORDER BY column_id';
        $result = $this->conn->queryCol($query);

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {

        $table = $this->conn->quote($table, 'text');
        $query = 'SELECT index_name name FROM user_indexes';
        $query.= ' WHERE table_name='.$table.' OR table_name='.strtoupper($table);
        $query.= ' AND generated=' .$this->conn->quote('N', 'text');
        $indexes = $this->conn->queryCol($query, 'text');

        $result = array();
        foreach ($indexes as $index) {
            $index = $this->_fixIndexName($index);
            if (!empty($index)) {
                $result[$index] = true;
            }
        }

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_change_key_case($result, $this->conn->options['field_case']);
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

        $query = 'SELECT table_name FROM sys.user_tables';
        $result = $this->conn->queryCol($query);

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
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

        if ($this->conn->options['emulate_database'] && $this->conn->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($this->conn->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username NOT LIKE '";
            $query.= $this->conn->options['database_name_prefix']."%'";
        } else {
            $query = 'SELECT username FROM sys.dba_users';
        }
        return $this->conn->queryCol($query);
    }
    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        $query = 'SELECT view_name FROM sys.user_views';
        $result = $this->conn->queryCol($query);

        if ($this->conn->options['portability'] & Doctrine::PORTABILITY_FIX_CASE
            && $this->conn->options['field_case'] == CASE_LOWER
        ) {
            $result = array_map(($this->conn->options['field_case'] == CASE_LOWER ? 'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
}
