<?php
$sess = Doctrine_Manager::getInstance()->openSession(new PDO("dsn","username","password"));

// gets the next ID from a sequence

$sess->getNextID($sequence);
?>
