<?php
/*
 *  $Id: Pessimistic.php 1468 2007-05-24 16:54:42Z zYne $
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
 * Offline locking of records comes in handy where you need to make sure that
 * a time-consuming task on a record or many records, which is spread over several
 * page requests can't be interfered by other users.
 *
 * @link        www.phpdoctrine.com
 * @author      Roman Borschel <roman@code-factory.org>
 * @author      Pierre Minnieur <pm@pierre-minnieur.de>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @version     $Revision: 1468 $
 */
class Doctrine_Locking_Manager_Pessimistic
{
    /**
     * The conn that is used by the locking manager
     *
     * @var Doctrine_Connection object
     */
    private $conn;
    /**
     * The database table name for the lock tracking
     */
    private $_lockTable = 'doctrine_lock_tracking';

    /**
     * Constructs a new locking manager object
     *
     * When the CREATE_TABLES attribute of the connection on which the manager
     * is supposed to work on is set to true, the locking table is created.
     *
     * @param Doctrine_Connection $conn The database connection to use
     */
    public function __construct(Doctrine_Connection $conn)
    {
        $this->conn = $conn;

        if ($this->conn->getAttribute(Doctrine::ATTR_EXPORT) & Doctrine::EXPORT_TABLES) {
            $columns = array();
            $columns['object_type']        = array('type'    => 'string',
                                                   'length'  => 50,
                                                   'notnull' => true,
                                                   'primary' => true);

            $columns['object_key']         = array('type'    => 'string',
                                                   'length'  => 250,
                                                   'notnull' => true,
                                                   'primary' => true);

            $columns['user_ident']         = array('type'    => 'string',
                                                   'length'  => 50,
                                                   'notnull' => true);

            $columns['timestamp_obtained'] = array('type'    => 'integer',
                                                   'length'  => 10,
                                                   'notnull' => true);

            $options = array('primary' => array('object_type', 'object_key'));
            try {
                $this->conn->export->createTable($this->_lockTable, $columns, $options);
            } catch(Exception $e) {

            }
        }
    }

    /**
     * Obtains a lock on a {@link Doctrine_Record}
     *
     * @param  Doctrine_Record $record     The record that has to be locked
     * @param  mixed           $userIdent  A unique identifier of the locking user
     * @return boolean  TRUE if the locking was successful, FALSE if another user
     *                  holds a lock on this record
     * @throws Doctrine_Locking_Exception  If the locking failed due to database errors
     */
    public function getLock(Doctrine_Record $record, $userIdent)
    {
        $objectType = $record->getTable()->getComponentName();
        $key        = $record->obtainIdentifier();

        $gotLock = false;
        $time = time();

        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        try {
            $dbh = $this->conn->getDbh();
            $dbh->beginTransaction();

            $stmt = $dbh->prepare('INSERT INTO ' . $this->_lockTable
                                  . ' (object_type, object_key, user_ident, timestamp_obtained)'
                                  . ' VALUES (:object_type, :object_key, :user_ident, :ts_obtained)');

            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->bindParam(':ts_obtained', $time);

            try {
                $stmt->execute();
                $gotLock = true;

            // we catch an Exception here instead of PDOException since we might also be catching Doctrine_Exception
            } catch(Exception $pkviolation) {
                // PK violation occured => existing lock!
            }

            if ( ! $gotLock) {
                $lockingUserIdent = $this->_getLockingUserIdent($objectType, $key);
                if ($lockingUserIdent !== null && $lockingUserIdent == $userIdent) {
                    $gotLock = true; // The requesting user already has a lock
                    // Update timestamp
                    $stmt = $dbh->prepare('UPDATE ' . $this->_lockTable 
                                          . ' SET timestamp_obtained = :ts'
                                          . ' WHERE object_type = :object_type AND'
                                          . ' object_key  = :object_key  AND'
                                          . ' user_ident  = :user_ident');
                    $stmt->bindParam(':ts', $time);
                    $stmt->bindParam(':object_type', $objectType);
                    $stmt->bindParam(':object_key', $key);
                    $stmt->bindParam(':user_ident', $lockingUserIdent);
                    $stmt->execute();
                }
            }
            $dbh->commit();
        } catch (Exception $pdoe) {
            $dbh->rollback();
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }

        return $gotLock;
    }

    /**
     * Releases a lock on a {@link Doctrine_Record}
     *
     * @param  Doctrine_Record $record    The record for which the lock has to be released
     * @param  mixed           $userIdent The unique identifier of the locking user
     * @return boolean  TRUE if a lock was released, FALSE if no lock was released
     * @throws Doctrine_Locking_Exception If the release procedure failed due to database errors
     */
    public function releaseLock(Doctrine_Record $record, $userIdent)
    {
        $objectType = $record->getTable()->getComponentName();
        $key        = $record->obtainIdentifier();

        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        try {
            $dbh = $this->conn->getDbh();
            $stmt = $dbh->prepare("DELETE FROM $this->_lockTable WHERE
                                        object_type = :object_type AND
                                        object_key  = :object_key  AND
                                        user_ident  = :user_ident");
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->execute();

            $count = $stmt->rowCount();

            return ($count > 0);
        } catch (PDOException $pdoe) {
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }
    }

    /**
     * Gets the unique user identifier of a lock
     *
     * @param  string $objectType  The type of the object (component name)
     * @param  mixed  $key         The unique key of the object
     * @return mixed  The unique user identifier for the specified lock
     * @throws Doctrine_Locking_Exception If the query failed due to database errors
     */
    private function _getLockingUserIdent($objectType, $key)
    {
        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        try {
            $dbh = $this->conn->getDbh();
            $stmt = $dbh->prepare('SELECT user_ident FROM ' . $this->_lockTable
                                  . ' WHERE object_type = :object_type AND object_key = :object_key');
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $success = $stmt->execute();

            if (!$success) {
                throw new Doctrine_Locking_Exception("Failed to determine locking user");
            }

            $userIdent = $stmt->fetchColumn();
        } catch (PDOException $pdoe) {
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }

        return $userIdent;
    }

    /**
     * Gets the identifier that identifies the owner of the lock on the given
     * record.
     *
     * @param Doctrine_Record $lockedRecord  The record.
     * @return mixed The unique user identifier that identifies the owner of the lock.
     */
    public function getLockOwner($lockedRecord)
    {
        $objectType = $lockedRecord->getTable()->getComponentName();
        $key        = $lockedRecord->obtainIdentifier();
        return $this->_getLockingUserIdent($objectType, $key);
    }

    /**
     * Releases locks older than a defined amount of seconds
     *
     * When called without parameters all locks older than 15 minutes are released.
     *
     * @param  integer $age  The maximum valid age of locks in seconds
     * @param  string  $objectType  The type of the object (component name)
     * @param  mixed   $userIdent The unique identifier of the locking user
     * @return integer The number of locks that have been released
     * @throws Doctrine_Locking_Exception If the release process failed due to database errors
     */
    public function releaseAgedLocks($age = 900, $objectType = null, $userIdent = null)
    {
        $age = time() - $age;

        try {
            $dbh = $this->conn->getDbh();
            $stmt = $dbh->prepare('DELETE FROM ' . $this->_lockTable . ' WHERE timestamp_obtained < :age');
            $stmt->bindParam(':age', $age);
            $query = 'DELETE FROM ' . $this->_lockTable . ' WHERE timestamp_obtained < :age';
            if ($objectType) {
                $query .= ' AND object_type = :object_type';
            }
            if ($userIdent) {
                $query .= ' AND user_ident = :user_ident';
            }
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':age', $age);
            if ($objectType) {
                $stmt->bindParam(':object_type', $objectType);
            }
            if ($userIdent) {
                $stmt->bindParam(':user_ident', $userIdent);
            }
            $stmt->execute();

            $count = $stmt->rowCount();

            return $count;
        } catch (PDOException $pdoe) {
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }
    }

}
