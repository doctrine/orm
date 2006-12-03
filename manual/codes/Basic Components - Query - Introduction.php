<?php
// initalizing a new Doctrine_Query (using the current connection)
$q = new Doctrine_Query();

// initalizing a new Doctrine_Query (using custom connection parameter)
// here $conn is an instance of Doctrine_Connection
$q = new Doctrine_Query($conn);
?>
