<?php
/**
 * Class for handling the pessimistic offline locking of {@link Doctrine_Record}s.
 * Offline locking of records comes in handy where you need to make sure that
 * a time-consuming task on a record or many records, which is spread over several
 * page requests can't be interfered by other users. 
 * 
 * @author  Roman Borschel <roman@code-factory.org>
 * @license LGPL
 * @since   1.0
 */
class Doctrine_Locking_Manager_Pessimistic
{
    /**
     * The datasource that is used by the locking manager
     *
     * @var PDO object
     */
    private $_dataSource;
    /**
     * The database table name for the lock tracking
     */
    private $lockTable = 'doctrine_lock_tracking';

    /**
     * Constructs a new locking manager object
     * 
     * When the CREATE_TABLES attribute of the session on which the manager
     * is supposed to work on is set to true, the locking table is created.
     *
     * @param Doctrine_Session $dataSource The database session to use
     */
    public function __construct(Doctrine_Session $dataSource)
    {
        $this->_dataSource = $dataSource;
        
        if($this->_dataSource->getAttribute(Doctrine::ATTR_CREATE_TABLES) === true)
        {
            $columns = array();
            $columns['object_type']        = array('string', 50, 'notnull|primary');
            $columns['object_key']         = array('string', 250, 'notnull|primary');
            $columns['user_ident']         = array('string', 50, 'notnull');
            $columns['timestamp_obtained'] = array('integer', 10, 'notnull');
            
            $dataDict = new Doctrine_DataDict($this->_dataSource->getDBH());
            $dataDict->createTable($this->lockTable, $columns);
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
        $key        = $record->getID();
        
        $gotLock = false;
        
        if(is_array($key))
        {
            // Composite key
            $key = implode('|', $key);
        }
        
        try
        {
            $dbh = $this->_dataSource->getDBH();
            $dbh->beginTransaction();
            
            $stmt = $dbh->prepare("INSERT INTO $this->lockTable
                                          (object_type, object_key, user_ident, timestamp_obtained)
                                   VALUES (:object_type, :object_key, :user_ident, :ts_obtained)");
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->bindParam(':ts_obtained', time());
            
            try {
                $stmt->execute();
                $gotLock = true;
            } catch(PDOException $pkviolation) {
                // PK violation occured => existing lock!
            }
            
            if(!$gotLock)
            {
                $lockingUserIdent = $this->_getLockingUserIdent($objectType, $key);
                if($lockingUserIdent !== null && $lockingUserIdent == $userIdent)
                {
                    $gotLock = true; // The requesting user already has a lock
                    // Update timestamp
                    $stmt = $dbh->prepare("UPDATE $this->lockTable SET timestamp_obtained = :ts
                                           WHERE object_type = :object_type AND
                                                 object_key  = :object_key  AND
                                                 user_ident  = :user_ident");
                    $stmt->bindParam(':ts', time());
                    $stmt->bindParam(':object_type', $objectType);
                    $stmt->bindParam(':object_key', $key);
                    $stmt->bindParam(':user_ident', $lockingUserIdent);
                    $stmt->execute();
                }
            }

            $dbh->commit();
                       
        }
        catch(PDOException $pdoe)
        {
            $dbh->rollBack();
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
        $key        = $record->getID();
        
        if(is_array($key))
        {
            // Composite key
            $key = implode('|', $key);
        }
        
        try
        {
            $dbh = $this->_dataSource->getDBH();
            $stmt = $dbh->prepare("DELETE FROM $this->lockTable WHERE
                                        object_type = :object_type AND
                                        object_key  = :object_key  AND
                                        user_ident  = :user_ident");
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->execute();
            
            $count = $stmt->rowCount();
            
            return ($count > 0);
                        
        }
        catch(PDOException $pdoe)
        {
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
        if(is_array($key))
        {
            // Composite key
            $key = implode('|', $key);
        }
        
        try
        {
            $dbh = $this->_dataSource->getDBH();
            $stmt = $dbh->prepare("SELECT user_ident
                                   FROM $this->lockTable
                                   WHERE object_type = :object_type AND object_key = :object_key");
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $success = $stmt->execute();
            
            if(!$success)
            {
                throw new Doctrine_Locking_Exception("Failed to determine locking user");
            }
            
            $user_ident = $stmt->fetchColumn();
        }
        catch(PDOException $pdoe)
        {
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }
        
        return $user_ident;
    }

    /**
     * Releases locks older than a defined amount of seconds
     * 
     * When called without parameters all locks older than 15 minutes are released.
     *
     * @param  integer $age  The maximum valid age of locks in seconds
     * @return boolean  TRUE if locks have been released, FALSE if no locks were released
     * @throws Doctrine_Locking_Exception If the release process failed due to database errors
     */
    public function releaseAgedLocks($age = 900)
    {
        $age = time() - $age;
        
        try
        {
            $dbh = $this->_dataSource->getDBH();
            $stmt = $dbh->prepare("DELETE FROM $this->lockTable WHERE timestamp_obtained < :age");
            $stmt->bindParam(':age', $age);
            $stmt->execute();
            
            $count = $stmt->rowCount();
            
            return ($count > 0);
        }
        catch(PDOException $pdoe)
        {
            throw new Doctrine_Locking_Exception($pdoe->getMessage());
        }
    }

}


?>