
<code type="php">
$conn = Doctrine_Manager::getInstance()
        ->openConnection(new PDO("dsn","username","password"));

$query = new Doctrine_Query($conn);
$query->from('User.Phonenumber')->limit(20);

$view  = new Doctrine_View($query, 'MyView');

// creating a database view
$view->create();

// dropping the view from the database
$view->drop();
</code>
