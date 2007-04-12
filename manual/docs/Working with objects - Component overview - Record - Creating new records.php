There are couple of ways for creating new records. Propably the easiest is using
native php new -operator. The other ways are calling Doctrine_Table::create() or Doctrine_Connection::create().
The last two exists only for backward compatibility. The recommended way of creating new objects is the new operator.

<code type="php">
$user = $conn->create("User");

// alternative way:

$table = $conn->getTable("User");

$user = $table->create();

// the simpliest way:

$user = new User();


// records support array access
$user["name"] = "John Locke";

// save user into database
$user->save();
</code>
