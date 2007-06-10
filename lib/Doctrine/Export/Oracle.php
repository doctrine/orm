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
class Doctrine_Export_Oracle extends Doctrine_Export
{
    /**
     * create a new database
     *
     * @param object $db database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function createDatabase($name)
    {
        if ( ! $this->conn->getAttribute(Doctrine::ATTR_EMULATE_DATABASE))
            throw new Doctrine_Export_Exception('database creation is only supported if the "emulate_database" attribute is enabled');

        $username   = sprintf($this->conn->getAttribute(Doctrine::ATTR_DB_NAME_FORMAT), $name);
        $password   = $this->conn->dsn['password'] ? $this->conn->dsn['password'] : $name;

        $tablespace = $this->conn->getAttribute(Doctrine::ATTR_DB_NAME_FORMAT)
                    ? ' DEFAULT TABLESPACE '.$this->conn->options['default_tablespace'] : '';

        $query  = 'CREATE USER ' . $username . ' IDENTIFIED BY ' . $password . $tablespace;
        $result = $this->conn->exec($query);

        try {
            $query = 'GRANT CREATE SESSION, CREATE TABLE, UNLIMITED TABLESPACE, CREATE SEQUENCE, CREATE TRIGGER TO ' . $username;
            $result = $this->conn->exec($query);
        } catch (Exception $e) {
            $query = 'DROP USER '.$username.' CASCADE';
            $result2 = $this->conn->exec($query);
        }
        return true;
    }
    /**
     * drop an existing database
     *
     * @param object $this->conn database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    public function dropDatabase($name)
    {
        if ( ! $this->conn->getAttribute(Doctrine::ATTR_EMULATE_DATABASE))
            throw new Doctrine_Export_Exception('database dropping is only supported if the
                                                       "emulate_database" option is enabled');

        $username = sprintf($this->conn->getAttribute(Doctrine::ATTR_DB_NAME_FORMAT), $name);

        return $this->conn->exec('DROP USER ' . $username . ' CASCADE');
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
    public function _makeAutoincrement($name, $table, $start = 1)
    {
        $table = strtoupper($table);
        $index_name  = $table . '_AI_PK';
        $definition = array(
            'primary' => true,
            'fields' => array($name => true),
        );
        $result = $this->createConstraint($table, $index_name, $definition);

        if (is_null($start)) {
            $this->conn->beginTransaction();
            $query = 'SELECT MAX(' . $this->conn->quoteIdentifier($name, true) . ') FROM ' . $this->conn->quoteIdentifier($table, true);
            $start = $this->conn->fetchOne($query);

            ++$start;
            $result = $this->createSequence($table, $start);
            $this->conn->commit();
        } else {
            $result = $this->createSequence($table, $start);
        }

        $sequence_name = $this->conn->getSequenceName($table);
        $trigger_name  = $this->conn->quoteIdentifier($table . '_AI_PK', true);
        $table = $this->conn->quoteIdentifier($table, true);
        $name  = $this->conn->quoteIdentifier($name, true);
        $trigger_sql = 'CREATE TRIGGER '.$trigger_name.'
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
        return $this->conn->exec($trigger_sql);
    }
    /**
     * drop an existing autoincrement sequence + trigger
     *
     * @param string $table name of the table
     * @return void
     */
    public function dropAutoincrement($table)
    {
        $table = strtoupper($table);
        $triggerName = $table . '_AI_PK';
        $trigger_name_quoted = $this->conn->quote($triggerName);
        $query = 'SELECT trigger_name FROM user_triggers';
        $query.= ' WHERE trigger_name='.$trigger_name_quoted.' OR trigger_name='.strtoupper($trigger_name_quoted);
        $trigger = $this->conn->fetchOne($query);

        if ($trigger) {
            $trigger_name  = $this->conn->quoteIdentifier($table . '_AI_PK', true);
            $trigger_sql = 'DROP TRIGGER ' . $trigger_name;

            // if throws exception, trigger for autoincrement PK could not be dropped
            $this->conn->exec($trigger_sql);

            // if throws exception, sequence for autoincrement PK could not be dropped
            $this->dropSequence($table);

            $indexName = $table . '_AI_PK';

            // if throws exception, primary key for autoincrement PK could not be dropped
            $this->dropConstraint($table, $indexName);
        }
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
     * @return void
     */
    public function createTable($name, array $fields, array $options = array())
    {
        $this->conn->beginTransaction();

        $result = parent::createTable($name, $fields, $options);

        foreach ($fields as $field_name => $field) {
            if (isset($field['autoincrement']) && $field['autoincrement']) {
                $result = $this->_makeAutoincrement($field_name, $name);
            }
        }

        $this->conn->commit();

        return $result;
    }
    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return void
     */
    public function dropTable($name)
    {
        //$this->conn->beginNestedTransaction();
        $result = $this->dropAutoincrement($name);
        $result = parent::dropTable($name);
        //$this->conn->completeNestedTransaction();
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
                    throw new Doctrine_Export_Exception('change type "' . $changeName . '" not yet supported');
            }
        }

        if ($check) {
            return false;
        }

        $name = $this->conn->quoteIdentifier($name, true);

        if ( ! empty($changes['add']) && is_array($changes['add'])) {
            $fields = array();
            foreach ($changes['add'] as $fieldName => $field) {
                $fields[] = $this->conn->getDeclaration($field['type'], $fieldName, $field);
            }
            $result = $this->conn->exec('ALTER TABLE ' . $name . ' ADD (' . implode(', ', $fields) . ')');
        }

        if ( ! empty($changes['change']) && is_array($changes['change'])) {
            $fields = array();
            foreach ($changes['change'] as $fieldName => $field) {
                $fields[] = $fieldName. ' ' . $this->conn->getDeclaration($field['definition']['type'], '', $field['definition']);
            }
            $result = $this->conn->exec('ALTER TABLE ' . $name . ' MODIFY (' . implode(', ', $fields) . ')');
        }

        if ( ! empty($changes['rename']) && is_array($changes['rename'])) {
            foreach ($changes['rename'] as $fieldName => $field) {
                $query = 'ALTER TABLE ' . $name . ' RENAME COLUMN ' . $this->conn->quoteIdentifier($fieldName, true)
                       . ' TO ' . $this->conn->quoteIdentifier($field['name']);

                $result = $this->conn->exec($query);
            }
        }

        if ( ! empty($changes['remove']) && is_array($changes['remove'])) {
            $fields = array();
            foreach ($changes['remove'] as $fieldName => $field) {
                $fields[] = $this->conn->quoteIdentifier($fieldName, true);
            }
            $result = $this->conn->exec('ALTER TABLE ' . $name . ' DROP COLUMN ' . implode(', ', $fields));
        }

        if ( ! empty($changes['name'])) {
            $changeName = $this->conn->quoteIdentifier($changes['name'], true);
            $result = $this->conn->exec('ALTER TABLE ' . $name . ' RENAME TO ' . $changeName);
        }
    }
    /** 
     * getForeignKeyDeferredDeclaration
     *
     * @return string
     */
    public function getForeignKeyDeferredDeclaration($deferred)
    {
        return ($deferred) ? 'INITIALLY DEFERRED DEFERRABLE' : '';
    }
    /**
     * create sequence
     *
     * @param object $this->conn database object that is extended by this class
     * @param string $seqName name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return void
     */
    public function createSequence($seqName, $start = 1)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->getSequenceName($seqName), true);
        $query = 'CREATE SEQUENCE ' . $sequenceName . ' START WITH ' . $start . ' INCREMENT BY 1 NOCACHE';
        $query.= ($start < 1 ? ' MINVALUE ' . $start : '');
        return $this->conn->exec($query);
    }
    /**
     * drop existing sequence
     *
     * @param object $this->conn database object that is extended by this class
     * @param string $seqName name of the sequence to be dropped
     * @return void
     */
    public function dropSequence($seqName)
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->getSequenceName($seqName), true);
        return $this->conn->exec('DROP SEQUENCE ' . $sequenceName);
    }
}
