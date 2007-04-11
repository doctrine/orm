<?php
// Doctrine_Manager controls all the connections 

$manager = Doctrine_Manager::getInstance();

// open first connection

$conn = $manager->openConnection(new PDO('dsn','username','password'), 'connection 1');

// open second connection

$conn2 = $manager->openConnection(new PDO('dsn2','username2','password2'), 'connection 2');

$manager->getCurrentConnection(); // $conn2

$manager->setCurrentConnection('connection 1');

$manager->getCurrentConnection(); // $conn

// iterating through connections

foreach($manager as $conn) {
    
}
?>
