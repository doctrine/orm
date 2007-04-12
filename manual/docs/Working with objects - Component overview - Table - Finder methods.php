Doctrine_Table provides basic finder methods. These finder methods are very fast and should be used if you only need to fetch 
data from one database table. If you need queries that use several components (database tables) use Doctrine_Connection::query().

<code type="php">
$table = $conn->getTable("User");

// find by primary key

$user = $table->find(2);

if($user !== false)
    print $user->name;


// get all users
foreach($table->findAll() as $user) {
    print $user->name;
}

// finding by dql
foreach($table->findByDql("name LIKE '%John%'") as $user) {
    print $user->created;
}
</code>
