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
Doctrine::autoload('Doctrine_Export');
/**
 * Doctrine_Export_Pgsql
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Export_Pgsql extends Doctrine_Export
{
   /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @throws PDOException
     * @return void
     */
    public function createDatabase($name)
    {
        $query  = 'CREATE DATABASE ' . $this->conn->quoteIdentifier($name);
        $this->conn->exec($query);
    }
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @throws PDOException
     * @access public
     */
    public function dropDatabase($name)
    {
        $query  = 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);
        $this->conn->exec($query);
    }
    /**
     * getAdvancedForeignKeyOptions
     * Return the FOREIGN KEY query section dealing with non-standard options
     * as MATCH, INITIALLY DEFERRED, ON UPDATE, ...
     *
     * @param array $definition         foreign key definition
     * @return string
     * @access protected
     */
    public function getAdvancedForeignKeyOptions(array $definition)
    {
        $query = '';
        if (isset($definition['match'])) {
            $query .= ' MATCH ' . $definition['match'];
        }
        if (isset($definition['onUpdate'])) {
            $query .= ' ON UPDATE ' . $definition['onUpdate'];
        }
        if (isset($definition['onDelete'])) {
            $query .= ' ON DELETE ' . $definition['onDelete'];
        }
        if (isset($definition['deferrable'])) {
            $query .= ' DEFERRABLE';
        } else {
            $query .= ' NOT DEFERRABLE';
        }
        if (isset($definition['feferred'])) {
            $query .= ' INITIALLY DEFERRED';
        } else {
            $query .= ' INITIALLY IMMEDIATE';
        }
        return $query;
    }

    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @throws Doctrine_Connection_Exception
     * @return boolean
     */
    public function alterTable($name, array $changes, $check)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                case 'remove':
                case 'change':
                case 'name':
                case 'rename':
                    break;
                default:
                    throw new Doctrine_Export_Exception('change type "' . $changeName . '\" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        if (isset($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                $query = 'ADD ' . $this->conn->getDeclaration($field['type'], $fieldName, $field);
                $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
            }
        }

        if (isset($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $query = 'DROP ' . $fieldName;
                $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
            }
        }

        if (isset($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                if (isset($field['type'])) {
                    $serverInfo = $this->conn->getServerVersion();

                    if (is_array($serverInfo) && $serverInfo['major'] < 8) {
                        throw new Doctrine_Export_Exception('changing column type for "'.$field['type'].'\" requires PostgreSQL 8.0 or above');
                    }
                    $query = 'ALTER ' . $fieldName . ' TYPE ' . $this->conn->datatype->getTypeDeclaration($field['definition']);
                    $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);;
                }
                if (array_key_exists('default', $field)) {
                    $query = 'ALTER ' . $fieldName . ' SET DEFAULT ' . $this->conn->quote($field['definition']['default'], $field['definition']['type']);
                    $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
                }
                if (!empty($field['notnull'])) {
                    $query = 'ALTER ' . $fieldName . ' ' . ($field['definition']['notnull'] ? 'SET' : 'DROP') . ' NOT NULL';
                    $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
                }
            }
        }

        if (isset($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $fieldName = $this->conn->quoteIdentifier($fieldName, true);
                $this->conn->exec('ALTER TABLE ' . $name . ' RENAME COLUMN ' . $fieldName . ' TO ' . $this->conn->quoteIdentifier($field['name'], true));
            }
        }

        $name = $this->conn->quoteIdentifier($name, true);
        if (isset($changes['name'])) {
            $changeName = $this->conn->quoteIdentifier($changes['name'], true);
            $this->conn->exec('ALTER TABLE ' . $name . ' RENAME TO ' . $changeName);
        }
    }
    /**
     * return RDBMS specific create sequence statement
     *
     * @throws Doctrine_Connection_Exception     if something fails at database level
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return string
     */
    public function createSequenceSql($sequenceName, $start = 1, array $options = array())
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName), true);
        return $this->conn->exec('CREATE SEQUENCE ' . $sequenceName . ' INCREMENT 1' .
                    ($start < 1 ? ' MINVALUE ' . $start : '') . ' START ' . $start);
    }
    /**
     * drop existing sequence
     *
     * @param string $sequenceName name of the sequence to be dropped
     */
    public function dropSequenceSql($sequenceName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($sequenceName), true);
        return 'DROP SEQUENCE ' . $sequenceName;
    }

}

