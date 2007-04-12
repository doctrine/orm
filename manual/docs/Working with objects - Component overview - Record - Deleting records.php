Deleting records in Doctrine is handled by Doctrine_Record::delete(), Doctrine_Collection::delete() and
Doctrine_Connection::delete() methods.

<code type="php">
$table = $conn->getTable("User");

$user = $table->find(2);

// deletes user and all related composite objects
if($user !== false)
    $user->delete();


$users = $table->findAll();


// delete all users and their related composite objects
$users->delete();
</code>
