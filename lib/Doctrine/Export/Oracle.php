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
 * Doctrine_Export_Oracle
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
class Doctrine_Export_Oracle extends Doctrine_Export {
    /**
     * create a new database
     *
     * @param object $db database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createDatabase($name) {
        if (!$db->options['emulate_database'])
            throw new Doctrine_Export_Oracle_Exception('database creation is only supported if the "emulate_database" option is enabled');


        $username = $db->options['database_name_prefix'].$name;
        $password = $db->dsn['password'] ? $db->dsn['password'] : $name;
        $tablespace = $db->options['default_tablespace']
            ? ' DEFAULT TABLESPACE '.$db->options['default_tablespace'] : '';

        $query = 'CREATE USER '.$username.' IDENTIFIED BY '.$password.$tablespace;
        $result = $db->standaloneQuery($query, null, true);

        $query = 'GRANT CREATE SESSION, CREATE TABLE, UNLIMITED TABLESPACE, CREATE SEQUENCE, CREATE TRIGGER TO '.$username;
        $result = $db->standaloneQuery($query, null, true);
        if (PEAR::isError($result)) {
            $query = 'DROP USER '.$username.' CASCADE';
            $result2 = $db->standaloneQuery($query, null, true);
            if (PEAR::isError($result2)) {
                return $db->raiseError($result2, null, null,
                    'could not setup the database user', __FUNCTION__);
            }
            return $result;
        }
    }
    /**
     * drop an existing database
     *
     * @param object $db database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropDatabase($name) {
        if (!$db->options['emulate_database'])
            throw new Doctrine_Export_Oracle_Exception('database dropping is only supported if the 
                                                       "emulate_database" option is enabled');


        $username = $db->options['database_name_prefix'].$name;
        return $db->standaloneQuery('DROP USER '.$username.' CASCADE', null, true);
    }
    /**
     * add an autoincrement sequence + trigger
     *
     * @param string $name  name of the PK field
     * @param string $table name of the table
     * @param string $start start value for the sequence
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    public function _makeAutoincrement($name, $table, $start = 1) {
        $table = strtoupper($table);
        $index_name  = $table . '_AI_PK';
        $definition = array(
            'primary' => true,
            'fields' => array($name => true),
        );
        $result = $db->manager->createConstraint($table, $index_name, $definition);
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'primary key for autoincrement PK could not be created', __FUNCTION__);
        }

        if (is_null($start)) {
            $db->beginTransaction();
            $query = 'SELECT MAX(' . $db->quoteIdentifier($name, true) . ') FROM ' . $db->quoteIdentifier($table, true);
            $start = $this->db->queryOne($query, 'integer');
            if (PEAR::isError($start)) {
                return $start;
            }
            ++$start;
            $result = $db->manager->createSequence($table, $start);
            $db->commit();
        } else {
            $result = $db->manager->createSequence($table, $start);
        }
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'sequence for autoincrement PK could not be created', __FUNCTION__);
        }
        $sequence_name = $db->getSequenceName($table);
        $trigger_name  = $db->quoteIdentifier($table . '_AI_PK', true);
        $table = $db->quoteIdentifier($table, true);
        $name  = $db->quoteIdentifier($name, true);
        $trigger_sql = '
CREATE TRIGGER '.$trigger_name.'
   BEFORE INSERT
   ON '.$table.'
   FOR EACH ROW
DECLARE
   last_Sequence NUMBER;
   last_InsertID NUMBER;
BEGIN
   SELECT '.$sequence_name.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   IF (:NEW.'.$name.' IS NULL OR :NEW.'.$name.' = 0) THEN
      SELECT '.$sequence_name.'.NEXTVAL INTO :NEW.'.$name.' FROM DUAL;
   ELSE
      SELECT NVL(Last_Number, 0) INTO last_Sequence
        FROM User_Sequences
       WHERE UPPER(Sequence_Name) = UPPER(\''.$sequence_name.'\');
      SELECT :NEW.id INTO last_InsertID FROM DUAL;
      WHILE (last_InsertID > last_Sequence) LOOP
         SELECT '.$sequence_name.'.NEXTVAL INTO last_Sequence FROM DUAL;
      END LOOP;
   END IF;
END;
';
        return $db->exec($trigger_sql);
    }
    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return mixed        MDB2_OK on success, a MDB2 error on failure
     * @access private
     */
    public function _dropAutoincrement($table) {
        $table = strtoupper($table);
        $trigger_name = $table . '_AI_PK';
        $trigger_name_quoted = $db->quote($trigger_name, 'text');
        $query = 'SELECT trigger_name FROM user_triggers';
        $query.= ' WHERE trigger_name='.$trigger_name_quoted.' OR trigger_name='.strtoupper($trigger_name_quoted);
        $trigger = $db->queryOne($query);

        if ($trigger) {
            $trigger_name  = $db->quoteIdentifier($table . '_AI_PK', true);
            $trigger_sql = 'DROP TRIGGER ' . $trigger_name;
            $result = $db->exec($trigger_sql);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'trigger for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $result = $db->manager->dropSequence($table);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'sequence for autoincrement PK could not be dropped', __FUNCTION__);
            }

            $index_name = $table . '_AI_PK';
            $result = $db->manager->dropConstraint($table, $index_name);
            if (PEAR::isError($result)) {
                return $db->raiseError($result, null, null,
                    'primary key for autoincrement PK could not be dropped', __FUNCTION__);
            }
        }

        return MDB2_OK;
    }
    /**
     * create a new table
     *
     * @param string $name     Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                        The indexes of the array entries are the names of the fields of the table an
     *                        the array entry values are associative arrays like those that are meant to be
     *                         passed with the field definitions to get[Type]Declaration() functions.
     *
     *                        Example
     *                        array(
     *
     *                            'id' => array(
     *                                'type' => 'integer',
     *                                'unsigned' => 1
     *                                'notnull' => 1
     *                                'default' => 0
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            ),
     *                            'password' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            )
     *                        );
     * @param array $options  An associative array of table options:
     *
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    public function createTable($name, $fields, $options = array()) {
        $db->beginNestedTransaction();
        $result = parent::createTable($name, $fields, $options);
        if (!PEAR::isError($result)) {
            foreach ($fields as $field_name => $field) {
                if (!empty($field['autoincrement'])) {
                    $result = $this->_makeAutoincrement($field_name, $name);
                }
            }
        }
        $db->completeNestedTransaction();
        return $result;
    }
    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropTable($name) {
        $db->beginNestedTransaction();
        $result = $this->_dropAutoincrement($name);
        if (!PEAR::isError($result)) {
            $result = parent::dropTable($name);
        }
        $db->completeNestedTransaction();
        return $result;
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
     *                                 be the same as defined by the MDB2 parser.
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
     *                                 as defined by the MDB2 parser.
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
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'change type "'.$change_name.'" not yet supported', __FUNCTION__);
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);

        if (!empty($changes['add']) && is_array($changes['add'])) {
            $fields = array();
            foreach ($changes['add'] as $field_name => $field) {
                $fields[] = $db->getDeclaration($field['type'], $field_name, $field);
            }
            $result = $db->exec("ALTER TABLE $name ADD (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $field_name => $field) {
                $fields[] = $field_name. ' ' . $db->getDeclaration($field['definition']['type'], '', $field['definition']);
            }
            $result = $db->exec("ALTER TABLE $name MODIFY (". implode(', ', $fields).')');
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $field_name => $field) {
                $field_name = $db->quoteIdentifier($field_name, true);
                $query = "ALTER TABLE $name RENAME COLUMN $field_name TO ".$db->quoteIdentifier($field['name']);
                $result = $db->exec($query);
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            $fields = array();
            foreach ($changes['remove'] as $field_name => $field) {
                $fields[] = $db->quoteIdentifier($field_name, true);
            }
            $result = $db->exec("ALTER TABLE $name DROP COLUMN ". implode(', ', $fields));
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if (!empty($changes['name'])) {
            $change_name = $db->quoteIdentifier($changes['name'], true);
            $result = $db->exec("ALTER TABLE $name RENAME TO ".$change_name);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
    }
    /**
     * create sequence
     *
     * @param object $db database object that is extended by this class
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return void
     */
    public function createSequence($seq_name, $start = 1) {
        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $query = "CREATE SEQUENCE $sequence_name START WITH $start INCREMENT BY 1 NOCACHE";
        $query.= ($start < 1 ? " MINVALUE $start" : '');
        return $db->exec($query);
    }
    /**
     * drop existing sequence
     *
     * @param object $db database object that is extended by this class
     * @param string $seq_name name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($seq_name) {
        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        return $db->exec("DROP SEQUENCE $sequence_name");
    }
}
