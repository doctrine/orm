<?php
$table = $session->getTable("User");

// find by primary key
try {
    $user = $table->find(2);
} catch(Doctrine_Find_Exception $e) {
    print "Couldn't find user";
}

// get all users
foreach($table->findAll() as $user) {
    print $user->name;
}

// finding by sql
foreach($table->findBySql("name LIKE '%John%'") as $user) {
    print $user->created;
}

// finding objects with DQL

$users = $session->query("FROM User WHERE User.name LIKE '%John%'");
?>
