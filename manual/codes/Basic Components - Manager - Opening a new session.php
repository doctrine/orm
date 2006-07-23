<?php
// Doctrine_Manager controls all the sessions 

$manager = Doctrine_Manager::getInstance();

// Doctrine_Session
// a script may have multiple open sessions 
// (= multiple database connections)
$dbh     = new PDO("dsn","username","password");
$session = $manager->openSession();

// or if you want to use Doctrine Doctrine_DB and its 
// performance monitoring capabilities

$dsn     = "schema://username:password@dsn/dbname";
$dbh     = Doctrine_DB::getConnection($dsn);
$session = $manager->openSession();
?>
