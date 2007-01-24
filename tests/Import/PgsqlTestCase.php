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
 * Doctrine_Import_Pgsql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Import_Pgsql_TestCase extends Doctrine_UnitTestCase 
{
    public function testListSequencesExecutesSql() 
    {
        $this->import->listSequences('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT
                                                relname
                                            FROM
                                                pg_class
                                            WHERE relkind = 'S' AND relnamespace IN
                                                (SELECT oid FROM pg_namespace
                                                 WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')");
    }
    public function testListTableColumnsExecutesSql()
    {
        $this->import->listTableColumns('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT
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
                                                  WHERE c.relname = 'table'
                                                        AND a.attnum > 0
                                                        AND a.attrelid = c.oid
                                                        AND a.atttypid = t.oid
                                                  ORDER BY a.attnum");
    }
    public function testListTableIndexesExecutesSql()
    {
        $this->import->listTableIndexes('table');
        
        $this->assertEqual($this->adapter->pop(), "SELECT
                                                        relname
                                                   FROM
                                                        pg_class
                                                   WHERE oid IN (
                                                        SELECT indexrelid
                                                        FROM pg_index, pg_class
                                                        WHERE pg_class.relname = 'table'
                                                            AND pg_class.oid=pg_index.indrelid
                                                            AND indisunique != 't'
                                                            AND indisprimary != 't'
                                                        )");
    }
    public function testListTablesExecutesSql()
    {
        $this->import->listTables();
        
        $q = "SELECT
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
                                                AND c.relname !~ '^pg_'";
        $this->assertEqual($this->adapter->pop(), $q);
    }
    public function testListDatabasesExecutesSql()
    {
        $this->import->listDatabases();
        
        $q = 'SELECT datname FROM pg_database';
        $this->assertEqual($this->adapter->pop(), $q);
    }
    public function testListUsersExecutesSql()
    {
        $this->import->listUsers();
        
        $q = 'SELECT usename FROM pg_user';
        $this->assertEqual($this->adapter->pop(), $q);
    }
    public function testListViewsExecutesSql()
    {
        $this->import->listViews();
        
        $q = 'SELECT viewname FROM pg_views';
        $this->assertEqual($this->adapter->pop(), $q);
    }
    public function testListFunctionsExecutesSql()
    {
        $this->import->listFunctions();
        
        $q = "SELECT
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
                                                     WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema'";
        $this->assertEqual($this->adapter->pop(), $q);
    }
    public function testListTableConstraintsExecutesSql()
    {
        $this->import->listTableConstraints('table');


        $q = "SELECT
                                                        relname
                                                   FROM
                                                        pg_class
                                                   WHERE oid IN (
                                                        SELECT indexrelid
                                                        FROM pg_index, pg_class
                                                        WHERE pg_class.relname = 'table'
                                                            AND pg_class.oid = pg_index.indrelid
                                                            AND (indisunique = 't' OR indisprimary = 't')
                                                        )";
        $this->assertEqual($this->adapter->pop(), $q);
    }
}
