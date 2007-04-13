The following code snippet demonstrates the use of Doctrine's pessimistic offline locking capabilities.

At the page where the lock is requested...
<code type="php">

// Get a locking manager instance
$lockingMngr = new Doctrine_Locking_Manager_Pessimistic();

try
{
    // Ensure that old locks which timed out are released 
    // before we try to acquire our lock
    // 300 seconds = 5 minutes timeout
    $lockingMngr->releaseAgedLocks(300);

    // Try to get the lock on a record
    $gotLock = $lockingMngr->getLock(
     // The record to lock. This can be any Doctrine_Record
                        $myRecordToLock,
    // The unique identifier of the user who is trying to get the lock
                       'Bart Simpson'
               );

    if($gotLock)
    {
        echo "Got lock!";
        // ... proceed
    }
    else
    {
        echo "Sorry, someone else is currently working on this record";
    }
}
catch(Doctrine_Locking_Exception $dle)
{
    echo $dle->getMessage();
    // handle the error
}

</code>

At the page where the transaction finishes...
<code type="php">
// Get a locking manager instance
$lockingMngr = new Doctrine_Locking_Manager_Pessimistic();

try
{
    if($lockingMngr->releaseLock($myRecordToUnlock, 'Bart Simpson'))
    {
        echo "Lock released";
    }
    else
    {
        echo "Record was not locked. No locks released.";
    }
}
catch(Doctrine_Locking_Exception $dle)
{
    echo $dle->getMessage();
    // handle the error
}
</code>
