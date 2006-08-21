<?php
$manager = Doctrine_Manager::getInstance();

// open new connection

$conn = $manager->openConnection(new PDO("dsn","username","password"));

// getting a table object

$table = $conn->getTable("User");
?>
