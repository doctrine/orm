In order to get your first application started you first
need to get an instance of Doctrine_Manager which handles all the connections (database connections).
The second thing to do is to open a new connection. 

<code type="php">
// Doctrine_Manager controls all the connections 

$manager = Doctrine_Manager::getInstance();

// Doctrine_Connection
// a script may have multiple open connections
// (= multiple database connections)
$dbh  = new PDO('dsn','username','password');
$conn = $manager->openConnection();

// or if you want to use Doctrine Doctrine_Db and its 
// performance monitoring capabilities

$dsn  = 'schema://username:password@dsn/dbname';
$dbh  = Doctrine_Db::getConnection($dsn);
$conn = $manager->openConnection();
</code>
