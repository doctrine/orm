Creating new record (database row) is very easy. You can either use the Doctrine_Connection::create() or Doctrine_Table::create()
method to do this or just simple use the new operator.

<code type="php">
$user = new User();
$user->name = 'Jack';

$group = $conn->create('Group');
$group->name = 'Drinking Club';

// saves all the changed objects into database

$conn->flush();
</code>
