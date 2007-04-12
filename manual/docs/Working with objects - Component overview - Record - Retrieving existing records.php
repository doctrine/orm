Doctrine provides many ways for record retrieval. The fastest ways for retrieving existing records
are the finder methods provided by Doctrine_Table. If you need to use more complex queries take a look at
DQL API and Doctrine_Connection::query method.

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

// finding objects with DQL

$users = $conn->query("FROM User WHERE User.name LIKE '%John%'");
</code>
