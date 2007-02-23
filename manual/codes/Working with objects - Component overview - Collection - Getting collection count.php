<?php
$users = $table->findAll();

$users->count();

// or

count($users); // Doctrine_Collection implements Countable interface
?>
