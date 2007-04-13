You can overload the query object by calling the dql query parts as methods. 

<code type="php">
$conn = Doctrine_Manager::getInstance()->openConnection(new PDO("dsn","username","password"));

$query = new Doctrine_Query($conn);

$query->from("User-b")
      ->where("User.name LIKE 'Jack%'")
      ->orderby("User.created")
      ->limit(5);

$users = $query->execute();

$query->from("User.Group.Phonenumber")
      ->where("User.Group.name LIKE 'Actors%'")
      ->orderby("User.name")
      ->limit(10)
      ->offset(5);
      
$users = $query->execute();
</code>
