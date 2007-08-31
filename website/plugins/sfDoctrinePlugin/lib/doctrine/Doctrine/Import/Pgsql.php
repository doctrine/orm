<?php
/*
 *  $Id: Pgsql.php 1889 2007-06-28 12:11:55Z zYne $
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
 * @author      Paul Cooper <pgc@ucecom.com>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision: 1889 $
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 */
class Doctrine_Import_Pgsql extends Doctrine_Import
{

    protected $sql = array(
                        'listDatabases' => 'SELECT datname FROM pg_database',
                        'listFunctions' => "SELECT
                                                proname
                                            FROM
                                                pg_proc pr,
                                                pg_type tp
                                            WHERE
                                                tp.oid = pr.prorettype
                                                AND pr.proisagg = FALSE
                                                AND tp.typname <> 'trigger'
                                                AND pr.pronamespace IN
                                                    (SELECT oid FROM pg_namespace
                                                     WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema'",
                        'listSequences' => "SELECT
                                                relname
                                            FROM
                                                pg_class
                                            WHERE relkind = 'S' AND relnamespace IN
                                                (SELECT oid FROM pg_namespace
                                                 WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')",
                        'listTables'    => "SELECT
                                                c.relname AS table_name
                                            FROM pg_class c, pg_user u
                                            WHERE c.relowner = u.usesysid
                                                AND c.relkind = 'r'
                                                AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname)
                                                AND c.relname !~ '^(pg_|sql_)'
                                            UNION
                                            SELECT c.relname AS table_name
                                            FROM pg_class c
                                            WHERE c.relkind = 'r'
                                                AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname)
                                                AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner)
                                                AND c.relname !~ '^pg_'",
                        'listViews'     => 'SELECT viewname FROM pg_views',
                        'listUsers'     => 'SELECT usename FROM pg_user',
                        'listTableConstraints' => "SELECT
                                                        relname
                                                   FROM
                                                        pg_class
                                                   WHERE oid IN (
                                                        SELECT indexrelid
                                                        FROM pg_index, pg_class
                                                        WHERE pg_class.relname = %s
                                                            AND pg_class.oid = pg_index.indrelid
                                                            AND (indisunique = 't' OR indisprimary = 't')
                                                        )",
                        'listTableIndexes'     => "SELECT
                                                        relname
                                                   FROM
                                                        pg_class
                                                   WHERE oid IN (
                                                        SELECT indexrelid
                                                        FROM pg_index, pg_class
                                                        WHERE pg_class.relname = %s
                                                            AND pg_class.oid=pg_index.indrelid
                                                            AND indisunique != 't'
                                                            AND indisprimary != 't'
                                                        )",
                        'listTableColumns'     => "SELECT
                                                        a.attnum,
                                                        a.attname AS field,
                                                        t.typname AS type,
                                                        format_type(a.atttypid, a.atttypmod) AS complete_type,
                                                        a.attnotnull AS isnotnull,
                                                        (SELECT 't'
                                                          FROM pg_index
                                                          WHERE c.oid = pg_index.indrelid
                                                          AND pg_index.indkey[0] = a.attnum
                                                          AND pg_index.indisprimary = 't'
                                                        ) AS pri,
                                                        (SELECT pg_attrdef.adsrc
                                                          FROM pg_attrdef
                                                          WHERE c.oid = pg_attrdef.adrelid
                                                          AND pg_attrdef.adnum=a.attnum
                                                        ) AS default
                                                  FROM pg_attribute a, pg_class c, pg_type t
                                                  WHERE c.relname = %s
                                                        AND a.attnum > 0
                                                        AND a.attrelid = c.oid
                                                        AND a.atttypid = t.oid
                                                  ORDER BY a.attnum",
                        );
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
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableConstraints($table)
    {
        $table = $this->conn->quote($table);
        $query = sprintf($this->sql['listTableConstraints'], $table);

        return $this->conn->fetchColumn($query);
    }
    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableColumns($table)
    {
        $table = $this->conn->quote($table);
        $query = sprintf($this->sql['listTableColumns'], $table);
        $result = $this->conn->fetchAssoc($query);

        $columns     = array();
        foreach ($result as $key => $val) {
            $val = array_change_key_case($val, CASE_LOWER);

            if (strtolower($val['type']) === 'varchar') {
                // get length from varchar definition
                $length = preg_replace('~.*\(([0-9]*)\).*~', '$1', $val['complete_type']);
                $val['length'] = $length;
            }
            
            $decl = $this->conn->dataDict->getPortableDeclaration($val);

            $description = array(
                'name'      => $val['field'],
                'ntype'     => $val['type'],
                'type'      => $decl['type'][0],
                'alltypes'  => $decl['type'],
                'length'    => $decl['length'],
                'fixed'     => $decl['fixed'],
                'unsigned'  => $decl['unsigned'],
                'notnull'   => ($val['isnotnull'] == ''),
                'default'   => $val['default'],
                'primary'   => ($val['pri'] == 't'),
            );
            $columns[$val['field']] = $description;
        }
        return $columns;
    }
    /**
     * list all indexes in a table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableIndexes($table)
    {
        $table = $this->conn->quote($table);
        $query = sprintf($this->sql['listTableIndexes'], $table);

        return $this->conn->fetchColumn($query);
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
     * lists table triggers
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableTriggers($table)
    {
        $query = 'SELECT trg.tgname AS trigger_name
                    FROM pg_trigger trg,
                         pg_class tbl
                   WHERE trg.tgrelid = tbl.oid';
        if ($table !== null) {
            $table = $this->conn->quote(strtoupper($table), 'string');
            $query .= " AND tbl.relname = $table";
        }
        return $this->conn->fetchColumn($query);
    }
    /**
     * list the views in the database that reference a given table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {
        return $this->conn->fetchColumn($query);
    }
}
