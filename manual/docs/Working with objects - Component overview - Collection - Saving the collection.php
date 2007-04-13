As with records the collection can be saved by calling the save method.

<code type="php">
$users = $table->findAll();

$users[0]->name = "Jack Daniels";

$users[1]->name = "John Locke";

$users->save();
</code>
