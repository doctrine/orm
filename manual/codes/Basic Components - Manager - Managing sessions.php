<?php
// Doctrine_Manager controls all the sessions 

$manager = Doctrine_Manager::getInstance();

// open first session

$session = $manager->openSession(new PDO("dsn","username","password"), "session 1");

// open second session

$session2 = $manager->openSession(new PDO("dsn2","username2","password2"), "session 2");

$manager->getCurrentSession(); // $session2

$manager->setCurrentSession("session 1");

$manager->getCurrentSession(); // $session

// iterating through sessions

foreach($manager as $session) {
    
}
?>
