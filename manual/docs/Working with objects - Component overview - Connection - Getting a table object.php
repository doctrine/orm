In order to get table object for specified record just call Doctrine_Record::getTable() or Doctrine_Connection::getTable().

<code type="php">
$manager = Doctrine_Manager::getInstance();

// open new connection

$conn = $manager->openConnection(new PDO('dsn','username','password'));

// getting a table object

$table = $conn->getTable('User');
</code>
