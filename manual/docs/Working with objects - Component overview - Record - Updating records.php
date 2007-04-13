Updating objects is very easy, you just call the Doctrine_Record::save() method. The other way
(perhaps even easier) is to call Doctrine_Connection::flush() which saves all objects. It should be noted though
that flushing is a much heavier operation than just calling save method.

<code type="php">
$table = $conn->getTable("User");


$user = $table->find(2);

if($user !== false) {
    $user->name = "Jack Daniels";
    
    $user->save();
}
</code>
