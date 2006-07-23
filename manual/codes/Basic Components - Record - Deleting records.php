<?php
$table = $session->getTable("User");

try {
    $user = $table->find(2);
} catch(Doctrine_Find_Exception $e) {
    print "Couldn't find user";
}

// deletes user and all related composite objects

$user->delete();


$users = $table->findAll();


// delete all users and their related composite objects
$users->delete();
?>
