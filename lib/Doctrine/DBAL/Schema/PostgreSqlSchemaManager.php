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
 * <http://www.phpdoctrine.org>.
 */

namespace Doctrine\DBAL\Schema;

/**
 * xxx
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @version     $Revision$
 * @since       2.0
 */
class PostgreSqlSchemaManager extends AbstractSchemaManager
{
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
            
            $decl = $this->_conn->getDatabasePlatform()->getPortableDeclaration($val);

            $description = array(
                'name'      => $val['field'],
                'ntype'     => $val['type'],
                'type'      => $decl['type'][0],
                'alltypes'  => $decl['type'],
                'length'    => $decl['length'],
                'fixed'     => $decl['fixed'],
                'unsigned'  => $decl['unsigned'],
                'notnull'   => ($val['isnotnull'] == true),
                'default'   => $val['default'],
                'primary'   => ($val['pri'] == 't'),
            );
            
            $matches = array(); 

            if (preg_match("/^nextval\('(.*)'(::.*)?\)$/", $description['default'], $matches)) { 
     
                $description['sequence'] = $this->_conn->formatter->fixSequenceName($matches[1]); 
                $description['default'] = null; 
            } 
            
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
        $table = $this->_conn->quote($table);
        $query = sprintf($this->sql['listTableIndexes'], $table);

        return $this->_conn->fetchColumn($query);
    }

    /**
     * lists tables
     *
     * @param string|null $database
     * @return array
     */
    public function listTables($database = null)
    {
        return $this->_conn->fetchColumn($this->sql['listTables']);
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
            $table = $this->_conn->quote(strtoupper($table), 'string');
            $query .= " AND tbl.relname = $table";
        }
        return $this->_conn->fetchColumn($query);
    }

    /**
     * list the views in the database that reference a given table
     *
     * @param string $table     database table name
     * @return array
     */
    public function listTableViews($table)
    {
        return $this->_conn->fetchColumn($query);
    }
}