The Doctrine_Collection method count returns the number of elements currently in the collection.

<code type="php">
$users = $table->findAll();

$users->count();

// or

count($users); // Doctrine_Collection implements Countable interface
</code>
