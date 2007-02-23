<?php
// initalizing a new Doctrine_Query (using the current connection)
$q = new Doctrine_Query();

// initalizing a new Doctrine_Query (using custom connection parameter)
// here $conn is an instance of Doctrine_Connection
$q = new Doctrine_Query($conn);

// an example using the create method
// here we simple fetch all users
$users = Doctrine_Query::create()->from('User')->execute();
?>
