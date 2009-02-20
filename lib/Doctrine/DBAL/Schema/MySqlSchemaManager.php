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
class MySqlSchemaManager extends AbstractSchemaManager
{
    /**
     * lists all database sequences
     *
     * @param string|null $database
     * @return array
     * @override
     */
    public function listSequences($database = null)
    {
        $query = 'SHOW TABLES';
        if ( ! is_null($database)) {
            $query .= ' FROM ' . $database;
        }
        $tableNames = $this->_conn->fetchColumn($query);

        return array_map(array($this->_conn->formatter, 'fixSequenceName'), $tableNames);
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableConstraints($table)
    {
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->_conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->_conn->fetchAssoc($query);

        $result = array();
        foreach ($indexes as $indexData) {
            if ( ! $indexData[$nonUnique]) {
                if ($indexData[$keyName] !== 'PRIMARY') {
                    $index = $this->_conn->formatter->fixIndexName($indexData[$keyName]);
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
     * @override
     */
    public function listTableForeignKeys($table)
    {
        $sql = 'SHOW CREATE TABLE ' . $this->_conn->quoteIdentifier($table, true);
        $definition = $this->_conn->fetchOne($sql);
        if (!empty($definition)) {
            $pattern = '/\bCONSTRAINT\s+([^\s]+)\s+FOREIGN KEY\b/i';
            if (preg_match_all($pattern, str_replace('`', '', $definition), $matches) > 1) {
                foreach ($matches[1] as $constraint) {
                    $result[$constraint] = true;
                }
            }
        }

        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE));
        }

        return $result;
    }

    /**
     * lists table constraints
     *
     * @param string $table     database table name
     * @return array
     * @override
     */
    public function listTableColumns($table)
    {
        $sql = 'DESCRIBE ' . $this->_conn->quoteIdentifier($table, true);
        $result = $this->_conn->fetchAssoc($sql);

        $description = array();
        $columns = array();
        foreach ($result as $key => $val) {

            $val = array_change_key_case($val, CASE_LOWER);

            $decl = $this->_conn->getDatabasePlatform()->getPortableDeclaration($val);

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
     * @override
     */
    public function listTableIndexes($table)
    {
        $keyName = 'Key_name';
        $nonUnique = 'Non_unique';
        if ($this->_conn->getAttribute(Doctrine::ATTR_PORTABILITY) & Doctrine::PORTABILITY_FIX_CASE) {
            if ($this->_conn->getAttribute(Doctrine::ATTR_FIELD_CASE) == CASE_LOWER) {
                $keyName = strtolower($keyName);
                $nonUnique = strtolower($nonUnique);
            } else {
                $keyName = strtoupper($keyName);
                $nonUnique = strtoupper($nonUnique);
            }
        }

        $table = $this->_conn->quoteIdentifier($table, true);
        $query = 'SHOW INDEX FROM ' . $table;
        $indexes = $this->_conn->fetchAssoc($query);


        $result = array();
        foreach ($indexes as $indexData) {
            if ($indexData[$nonUnique] && ($index = $this->_conn->formatter->fixIndexName($indexData[$keyName]))) {
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
     * @override
     */
    public function listTables($database = null)
    {
        return $this->_conn->fetchColumn($this->_conn->getDatabasePlatform()
                ->getListTablesSql());
    }

    /**
     * lists database views
     *
     * @param string|null $database
     * @return array
     * @override
     */
    public function listViews($database = null)
    {
        if ( ! is_null($database)) {
            $query = sprintf($this->sql['listViews'], ' FROM ' . $database);
        }

        return $this->_conn->fetchColumn($query);
    }

    /**
     * create sequence
     *
     * @param string    $sequenceName name of the sequence to be created
     * @param string    $start        start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                              'type'    => 'innodb',
     *                          );
     * @return boolean
     * @override
     */
    public function createSequence($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->_conn->quoteIdentifier($this->_conn->getSequenceName($sequenceName), true);
        $seqcolName     = $this->_conn->quoteIdentifier($this->_conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);

        $optionsStrings = array();

        if (isset($options['comment']) && ! empty($options['comment'])) {
            $optionsStrings['comment'] = 'COMMENT = ' . $this->_conn->quote($options['comment'], 'string');
        }

        if (isset($options['charset']) && ! empty($options['charset'])) {
            $optionsStrings['charset'] = 'DEFAULT CHARACTER SET ' . $options['charset'];

            if (isset($options['collate'])) {
                $optionsStrings['collate'] .= ' COLLATE ' . $options['collate'];
            }
        }

        $type = false;

        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = $this->_conn->default_table_type;
        }
        if ($type) {
            $optionsStrings[] = 'ENGINE = ' . $type;
        }

        try {
            $query  = 'CREATE TABLE ' . $sequenceName
                    . ' (' . $seqcolName . ' INT NOT NULL AUTO_INCREMENT, PRIMARY KEY ('
                    . $seqcolName . '))';

            if (!empty($options_strings)) {
                $query .= ' '.implode(' ', $options_strings);
            }

            $res    = $this->_conn->exec($query);
        } catch(Doctrine\DBAL\ConnectionException $e) {
            throw \Doctrine\Common\DoctrineException::updateMe('could not create sequence table');
        }

        if ($start == 1) {
            return true;
       }

        $query  = 'INSERT INTO ' . $sequenceName
                . ' (' . $seqcolName . ') VALUES (' . ($start - 1) . ')';

        $res    = $this->_conn->exec($query);

      // Handle error
      try {
          $res = $this->_conn->exec('DROP TABLE ' . $sequenceName);
      } catch(Doctrine\DBAL\ConnectionException $e) {
          throw \Doctrine\Common\DoctrineException::updateMe('could not drop inconsistent sequence table');
      }

      return $res;
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $table
     * @param unknown_type $name
     * @return unknown
     * @override
     */
    public function dropForeignKey($table, $name)
    {
        $table = $this->_conn->quoteIdentifier($table);
        $name  = $this->_conn->quoteIdentifier($name);
        return $this->_conn->exec('ALTER TABLE ' . $table . ' DROP FOREIGN KEY ' . $name);
    }
}