You can access the elements of Doctrine_Collection with set() and get() methods or with ArrayAccess interface.

<code type="php">
$table = $conn->getTable("User");

$users = $table->findAll();

// accessing elements with ArrayAccess interface

$users[0]->name = "Jack Daniels";

$users[1]->name = "John Locke";

// accessing elements with get()

print $users->get(1)->name;
</code>
