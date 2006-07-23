<?php
$manager = Doctrine_Manager::getInstance();

// open new session

$session = $manager->openSession(new PDO("dsn","username","password"));

// getting a table object

$table = $session->getTable("User");   
?>
