<?php
$sess = Doctrine_Manager::getInstance()->openSession(new PDO("dsn","username","password"));

// select first ten rows starting from the row 20

$sess->select("select * from user",10,20);
?>
