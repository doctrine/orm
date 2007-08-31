<?php
/*
 *  $Id: Mssql.php 1697 2007-06-14 20:18:25Z zYne $
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
 * Doctrine_Export_Mssql
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author      Frank M. Kromann <frank@kromann.info> (PEAR MDB2 Mssql driver)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1697 $
 */
class Doctrine_Export_Mssql extends Doctrine_Export
{
  /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return void
     */
    public function createDatabase($name)
    {
        $name = $this->conn->quoteIdentifier($name, true);
        $query = "CREATE DATABASE $name";
        if ($this->conn->options['database_device']) {
            $query.= ' ON '.$this->conn->options['database_device'];
            $query.= $this->conn->options['database_size'] ? '=' .
                     $this->conn->options['database_size'] : '';
        }
        return $this->conn->standaloneQuery($query, null, true);
    }
    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return void
     */
    public function dropDatabase($name)
    {
        $name = $this->conn->quoteIdentifier($name, true);
        return $this->conn->standaloneQuery('DROP DATABASE ' . $name, null, true);
    }

    /**
     * Override the parent method.
     *
     * @return string The string required to be placed between "CREATE" and "TABLE"
     *                to generate a temporary table, if possible.
     */
    public function getTemporaryTableQuery()
    {
        return '';
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
     * @return void
     */
    public function alterTable($name, array $changes, $check)
    {
        foreach ($changes as $changeName => $change) {
            switch ($changeName) {
                case 'add':
                    break;
                case 'remove':
                    break;
                case 'name':
                case 'rename':
                case 'change':
                default:
                    throw new Doctrine_Export_Exception('alterTable: change type "' . $changeName . '" not yet supported');
            }
        }

        $query = '';
        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $query .= 'ADD ' . $this->conn->getDeclaration($field['type'], $fieldName, $field);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $fieldName => $field) {
                if ($query) {
                    $query .= ', ';
                }
                $field_name = $this->conn->quoteIdentifier($fieldName, true);
                $query .= 'DROP COLUMN ' . $fieldName;
            }
        }

        if ( ! $query) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);
        return $this->conn->exec('ALTER TABLE ' . $name . ' ' . $query);
    }
    /**
     * create sequence
     *
     * @param string $seqName name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return string
     */
    public function createSequence($seqName, $start = 1, array $options = array())
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->getSequenceName($seqName), true);
        $seqcolName = $this->conn->quoteIdentifier($this->conn->options['seqcol_name'], true);
        $query = 'CREATE TABLE ' . $sequenceName . ' (' . $seqcolName .
                 ' INT PRIMARY KEY CLUSTERED IDENTITY(' . $start . ', 1) NOT NULL)';

        $res = $this->conn->exec($query);

        if ($start == 1) {
            return true;
        }

        try {
            $query = 'SET IDENTITY_INSERT ' . $sequenceName . ' ON ' .
                     'INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES ( ' . $start . ')';
            $res = $this->conn->exec($query);
        } catch (Exception $e) {
            $result = $this->conn->exec('DROP TABLE ' . $sequenceName);
        }
        return true;
    }
    /**
     * This function drops an existing sequence
     *
     * @param string $seqName      name of the sequence to be dropped
     * @return void
     */
    public function dropSequenceSql($seqName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->getSequenceName($seqName), true);
        return 'DROP TABLE ' . $sequenceName;
    }
}
