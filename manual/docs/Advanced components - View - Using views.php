

<code type="php">
$conn = Doctrine_Manager::getInstance()
        ->openConnection(new PDO("dsn","username","password"));

$query = new Doctrine_Query($conn);
$query->from('User.Phonenumber')->limit(20);

// hook the query into appropriate view
$view  = new Doctrine_View($query, 'MyView');

// now fetch the data from the view
$coll  = $view->execute();
</code>
