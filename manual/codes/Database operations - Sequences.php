<?php
$sess = Doctrine_Manager::getInstance()->openConnection(new PDO("dsn","username","password"));

// gets the next ID from a sequence

$sess->getNextID($sequence);
?>
