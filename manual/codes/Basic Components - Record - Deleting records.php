<?php
$table = $session->getTable("User");

$user = $table->find(2);

// deletes user and all related composite objects
if($user !== false)
    $user->delete();


$users = $table->findAll();


// delete all users and their related composite objects
$users->delete();
?>
