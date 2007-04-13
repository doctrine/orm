
<code type="php">
$table = $conn->getTable("User");

$table->setEventListener(new MyListener2());

// retrieve user whose primary key is 2
$user    = $table->find(2);

$user->name = "John Locke";

// update event will be listened and current time will be assigned to the field 'updated' 
$user->save();
</code>
