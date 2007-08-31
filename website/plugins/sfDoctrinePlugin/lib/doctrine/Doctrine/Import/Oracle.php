<?php
/*
 *  $Id: Oracle.php 1889 2007-06-28 12:11:55Z zYne $
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
 * @version     $Revision: 1889 $
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
        if ( ! $this->conn->getAttribute(Doctrine::ATTR_EMULATE_DATABASE)) {
            throw new Doctrine_Import_Exception('database listing is only supported if the "emulate_database" option is enabled');
        }
        /**
        if ($this->conn->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($this->conn->getAttribute(['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username LIKE '";
            $query.= $this->conn->options['database_name_prefix']."%'";
        } else {
        */
        $query   = 'SELECT username FROM sys.dba_users';

        $result2 = $this->conn->standaloneQuery($query);
        $result  = $result2->fetchColumn();

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

        return $this->conn->fetchColumn($query);
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

        $tableNames = $this->conn->fetchColumn($query);

        return array_map(array($this->conn->formatter, 'fixSequenceName'), $tableNames);
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

        $query = 'SELECT index_name name FROM user_constraints'
               . ' WHERE table_name = ' . $table . ' OR table_name = ' . strtoupper($table);

        $constraints = $this->conn->fetchColumn($query);

        return array_map(array($this->conn->formatter, 'fixIndexName'), $constraints);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $table  = strtoupper($table);
        $sql    = "SELECT column_name, data_type, data_length, nullable, data_default, data_scale, data_precision FROM all_tab_columns"
                . " WHERE table_name = '" . $table . "' ORDER BY column_name";

        $result = $this->conn->fetchAssoc($sql);

        foreach($result as $val) {
            $val = array_change_key_case($val, CASE_LOWER);
            $decl = $this->conn->dataDict->getPortableDeclaration($val);


            $descr[$val['column_name']] = array(
               'name'       => $val['column_name'],
               'notnull'    => (bool) ($val['nullable'] === 'N'),
               'ntype'      => $val['data_type'],
               'type'       => $decl['type'][0],
               'alltypes'   => $decl['type'],
               'fixed'      => $decl['fixed'],
               'unsigned'   => $decl['unsigned'],
               'default'    => $val['data_default'],
               'length'     => $val['data_length'],
               'precision'  => $val['data_precision'],
               'scale'      => $val['scale'],
            );
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
        $query = 'SELECT index_name name FROM user_indexes'
               . ' WHERE table_name = ' . $table . ' OR table_name = ' . strtoupper($table)
               . ' AND generated = ' . $this->conn->quote('N', 'text');

        $indexes = $this->conn->fetchColumn($query);

        return array_map(array($this->conn->formatter, 'fixIndexName'), $indexes);
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
        return $this->conn->fetchColumn($query);
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
    	/**
        if ($this->conn->options['emulate_database'] && $this->conn->options['database_name_prefix']) {
            $query = 'SELECT SUBSTR(username, ';
            $query.= (strlen($this->conn->options['database_name_prefix'])+1);
            $query.= ") FROM sys.dba_users WHERE username NOT LIKE '";
            $query.= $this->conn->options['database_name_prefix']."%'";
        } else {
        */

        $query = 'SELECT username FROM sys.dba_users';
        //}

        return $this->conn->fetchColumn($query);
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
        return $this->conn->fetchColumn($query);
    }
}
