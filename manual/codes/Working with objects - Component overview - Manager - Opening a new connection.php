<?php
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
?>
