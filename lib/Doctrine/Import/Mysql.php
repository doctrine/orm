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
 * @subpackage  Import
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Import_Mysql extends Doctrine_Import
{
    protected $sql  = array(
                            'showDatabases'   => 'SHOW DATABASES',
                            'listTableFields' => 'DESCRIBE %s',
                            'listSequences'   => 'SHOW TABLES',
                            'listTables'      => 'SHOW TABLES',
                            'listUsers'       => 'SELECT DISTINCT USER FROM USER',
                            'listViews'       => "SHOW FULL TABLES %s WHERE Table_type = 'VIEW'",
                            );

    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     */
    public function listSequences($database = null)
    {
        $query = 'SHOW TABLES';
        if ( ! is_null($database)) {
            $query .= ' FROM ' . $database;
        }
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
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->conn->fetchAssoc($query);

        $result = array();
        foreach ($indexes as $indexData) {
            if ( ! $indexData[$nonUnique]) {
                if ($indexData[$keyName] !== 'PRIMARY') {
                    $index = $this->conn->formatter->fixIndexName($indexData[$keyName]);
                } else {
                    $index = 'PRIMARY';
                }
                if ( ! empty($index)) {
                    $result[] = $index;
                }
            }
        }
        return $result;
    }

    /**
     * lists table foreign keys
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableForeignKeys($table)
    {
        $sql = 'SHOW CREATE TABLE ' . $this->conn->quoteIdentifier($table, true);
        $definition = $this->conn->fetchOne($sql);
        if (!empty($definition)) {
            $pattern = '/\bCONSTRAINT\s+([^\s]+)\s+FOREIGN KEY\b/i';
            if (preg_match_all($pattern, str_replace('`', '', $definition), $matches) > 1) {
                foreach ($matches[1] as $constraint) {
                    $result[$constraint] = true;
                }
            }
        }

        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $this->conn->getAttribute(Doctrine::ATTR_FIELD_CASE));
        }

        return $result;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $sql = 'DESCRIBE ' . $this->conn->quoteIdentifier($table, true);
        $result = $this->conn->fetchAssoc($sql);

        $description = array();
        $columns = array();
        foreach ($result as $key => $val) {

            $val = array_change_key_case($val, CASE_LOWER);

            $decl = $this->conn->dataDict->getPortableDeclaration($val);

            $values = isset($decl['values']) ? $decl['values'] : array();

            $description = array(
                          'name'          => $val['field'],
                          'type'          => $decl['type'][0],
                          'alltypes'      => $decl['type'],
                          'ntype'         => $val['type'],
                          'length'        => $decl['length'],
                          'fixed'         => $decl['fixed'],
                          'unsigned'      => $decl['unsigned'],
                          'values'        => $values,
                          'primary'       => (strtolower($val['key']) == 'pri'),
                          'default'       => $val['default'],
                          'notnull'       => (bool) ($val['null'] != 'YES'),
                          'autoincrement' => (bool) (strpos($val['extra'], 'auto_increment') !== false),
                          );
            $columns[$val['field']] = $description;
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
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->conn->fetchAssoc($query);


        $result = array();
        foreach ($indexes as $indexData) {
            if ($indexData[$nonUnique] && ($index = $this->conn->formatter->fixIndexName($indexData[$keyName]))) {
                $result[] = $index;
            }
        }
        return $result;
    }

    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        return $this->conn->fetchColumn($this->sql['listTables']);
    }

    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     */
    public function listViews($database = null)
    {
        if ( ! is_null($database)) {
            $query = sprintf($this->sql['listViews'], ' FROM ' . $database);
        }

        return $this->conn->fetchColumn($query);
    }
}
