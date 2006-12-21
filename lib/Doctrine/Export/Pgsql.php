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
class Doctrine_Export_Pgsql extends Doctrine_Export {
   /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @throws PDOException
     * @return void
     */
    public function createDatabase($name) {
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
    public function dropDatabase($name) {
        $query  = 'DROP DATABASE ' . $this->conn->quoteIdentifier($name);
        $this->conn->exec($query);
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
     * @throws PDOException
     * @return boolean
     */
    public function alterTable($name, $changes, $check) {
        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
                case 'add':
                case 'remove':
                case 'change':
                case 'name':
                case 'rename':
                break;
                default:
                    throw new Doctrine_Export_Pgsql_Exception('change type "'.$change_name.'\" not yet supported');
            }
        }

        if ($check) {
            return true;
        }

        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                $query = 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field);
                $this->dbh->query("ALTER TABLE $name $query");
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                $query = 'DROP ' . $field_name;
                $this->dbh->query("ALTER TABLE $name $query");
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            foreach ($changes['change'] as $field_name => $field) {
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                if (!empty($field['type'])) {
                    $server_info = $db->getServerVersion();

                    if (is_array($server_info) && $server_info['major'] < 8)
                        throw new Doctrine_Export_Pgsql_Exception('changing column type for "'.$change_name.'\" requires PostgreSQL 8.0 or above');
                    
                    $query = "ALTER $field_name TYPE ".$db->datatype->getTypeDeclaration($field['definition']);
                    $this->dbh->query("ALTER TABLE $name $query");
                }
                if (array_key_exists('default', $field)) {
                    $query = "ALTER $field_name SET DEFAULT ".$db->quote($field['definition']['default'], $field['definition']['type']);
                    $this->dbh->query("ALTER TABLE $name $query");
                }
                if (!empty($field['notnull'])) {
                    $query = "ALTER $field_name ".($field['definition']['notnull'] ? "SET" : "DROP").' NOT NULL';
                    $this->dbh->query("ALTER TABLE $name $query");

                }
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $this->conn->quoteIdentifier($field_name, true);
                $this->dbh->query("ALTER TABLE $name RENAME COLUMN $field_name TO ".$this->conn->quoteIdentifier($field['name'], true));
            }
        }

        $name = $this->conn->quoteIdentifier($name, true);
        if (!empty($changes['name'])) {
            $change_name = $this->conn->quoteIdentifier($changes['name'], true);
            $this->dbh->query("ALTER TABLE $name RENAME TO ".$change_name);
        }
    }
}
?>
