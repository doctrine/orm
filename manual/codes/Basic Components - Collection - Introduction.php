<?php
$conn = Doctrine_Manager::getInstance()
    ->openConnection(new PDO("dsn", "username", "pw"));

// initalizing a new collection
$users = new Doctrine_Collection($conn->getTable('User'));

// alternative (propably easier)
$users = new Doctrine_Collection('User');

// adding some data
$coll[0]->name = 'Arnold';

$coll[1]->name = 'Somebody';

// finally save it!
$coll->save();
?>
