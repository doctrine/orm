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
 * Doctrine_Export_Sqlite
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
class Doctrine_Export_Sqlite extends Doctrine_Export
{
    /**
     * drop an existing database
     *
     * @param string $name                  name of the database that should be dropped
     * @throws Doctrine_Export_Exception    if the database file does not exist
     * @throws Doctrine_Export_Exception    if something failed during the removal of the database file
     * @return void
     */
    public function dropDatabase($name)
    {
        $databaseFile = $this->conn->getDatabaseFile($name);
        if (!@file_exists($databaseFile)) {
            throw new Doctrine_Export_Exception('database does not exist');
        }
        $result = @unlink($databaseFile);
        if ( ! $result) {
            throw new Doctrine_Export_Exception('could not remove the database file');
        }
    }

    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name         name of the index to be created
     * @param array     $definition        associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function support() to determine whether the DBMS driver can manage indexes.

     *                                 Example
     *                                    array(
     *                                        'fields' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @throws PDOException
     * @return void
     */
    public function createIndexSql($table, $name, array $definition)
    {
        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->formatter->getIndexName($name);
        $query = 'CREATE INDEX ' . $name . ' ON ' . $table;
        $query .= ' (' . $this->getIndexFieldDeclarationList($definition['fields']) . ')';

        return $query;
    }
    /**
     * getIndexFieldDeclarationList
     * Obtain DBMS specific SQL code portion needed to set an index
     * declaration to be used in statements like CREATE TABLE.
     *
     * @return string   
     */
    public function getIndexFieldDeclarationList(array $fields)
    {
    	$declFields = array();

        foreach ($fields as $fieldName => $field) {
            $fieldString = $fieldName;

            if (is_array($field)) {
                if (isset($field['sorting'])) {
                    $sort = strtoupper($field['sorting']);
                    switch ($sort) {
                        case 'ASC':
                        case 'DESC':
                            $fieldString .= ' ' . $sort;
                            break;
                        default:
                            throw new Doctrine_Export_Exception('Unknown index sorting option given.');
                    }
                }
            } else {
                $fieldString = $field;
            }
            $declFields[] = $fieldString;
        }
        return implode(', ', $declFields);
    }
    /**
     * create a new table
     *
     * @param string $name   Name of the database that should be created
     * @param array $fields  Associative array that contains the definition of each field of the new table
     *                       The indexes of the array entries are the names of the fields of the table an
     *                       the array entry values are associative arrays like those that are meant to be
     *                       passed with the field definitions to get[Type]Declaration() functions.
     *                          array(
     *                              'id' => array(
     *                                  'type' => 'integer',
     *                                  'unsigned' => 1
     *                                  'notnull' => 1
     *                                  'default' => 0
     *                              ),
     *                              'name' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              ),
     *                              'password' => array(
     *                                  'type' => 'text',
     *                                  'length' => 12
     *                              )
     *                          );
     * @param array $options  An associative array of table options:
     *
     * @return void
     */
    public function createTableSql($name, array $fields, array $options = array(), $exportForeignKeySql = true)
    {
        if ( ! $name) {
            throw new Doctrine_Export_Exception('no valid table name specified');
        }
        
        if (empty($fields)) {
            throw new Doctrine_Export_Exception('no fields specified for table '.$name);
        }
        $queryFields = $this->getFieldDeclarationList($fields);
        
        $autoinc = false;
        foreach($fields as $field) {
            if(isset($field['autoincrement']) && $field['autoincrement'] || 
              (isset($field['autoinc']) && $field['autoinc'])) {
                $autoinc = true;
                break;
            }
        }

        if ( ! $autoinc && isset($options['primary']) && ! empty($options['primary'])) {
            $queryFields.= ', PRIMARY KEY('.implode(', ', array_values($options['primary'])).')';
        }

        $name  = $this->conn->quoteIdentifier($name, true);
        $sql   = 'CREATE TABLE ' . $name . ' (' . $queryFields;

        if ($check = $this->getCheckDeclaration($fields)) {
            $sql .= ', ' . $check;
        }

        if (isset($options['checks']) && $check = $this->getCheckDeclaration($options['checks'])) {
            $sql .= ', ' . $check;
        }

        $sql .= ')';

        $query[] = $sql;

        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $query[] = $this->createIndexSql($name, $index, $definition);
            }
        }
        return $query;
        
        
        /**
        try {

            if ( ! empty($fk)) {
                $this->conn->beginTransaction();
            }

            $ret   = $this->conn->exec($query);

            if ( ! empty($fk)) {
                foreach ($fk as $definition) {

                    $query = 'CREATE TRIGGER doctrine_' . $name . '_cscd_delete '
                           . 'AFTER DELETE ON ' . $name . ' FOR EACH ROW '
                           . 'BEGIN '
                           . 'DELETE FROM ' . $definition['foreignTable'] . ' WHERE ';

                    $local = (array) $definition['local'];
                    foreach((array) $definition['foreign'] as $k => $field) {
                        $query .= $field . ' = old.' . $local[$k] . ';';
                    }

                    $query .= 'END;';

                    $this->conn->exec($query);
                }

                $this->conn->commit();
            }


        } catch(Doctrine_Exception $e) {

            $this->conn->rollback();

            throw $e;
        }
        */
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
     * create sequence
     *
     * @param string    $seqName        name of the sequence to be created
     * @param string    $start          start value of the sequence; default is 1
     * @param array     $options  An associative array of table options:
     *                          array(
     *                              'comment' => 'Foo',
     *                              'charset' => 'utf8',
     *                              'collate' => 'utf8_unicode_ci',
     *                          );
     * @return boolean
     */
    public function createSequence($seqName, $start = 1, array $options = array())
    {
        $sequenceName   = $this->conn->quoteIdentifier($this->conn->getSequenceName($seqName), true);
        $seqcolName     = $this->conn->quoteIdentifier($this->conn->getAttribute(Doctrine::ATTR_SEQCOL_NAME), true);
        $query          = 'CREATE TABLE ' . $sequenceName . ' (' . $seqcolName . ' INTEGER PRIMARY KEY DEFAULT 0 NOT NULL)';

        $this->conn->exec($query);

        if ($start == 1) {
            return true;
        }

        try {
            $this->conn->exec('INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (' . ($start-1) . ')');
            return true;
        } catch(Doctrine_Connection_Exception $e) {
            // Handle error    

            try {
                $result = $db->exec('DROP TABLE ' . $sequenceName);
            } catch(Doctrine_Connection_Exception $e) {
                throw new Doctrine_Export_Exception('could not drop inconsistent sequence table');
            }
        }
        throw new Doctrine_Export_Exception('could not create sequence table');
    }
    /**
     * drop existing sequence
     *
     * @param string $sequenceName      name of the sequence to be dropped
     * @return string
     */
    public function dropSequenceSql($sequenceName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->getSequenceName($sequenceName), true);

        return 'DROP TABLE ' . $sequenceName;
    }
}
