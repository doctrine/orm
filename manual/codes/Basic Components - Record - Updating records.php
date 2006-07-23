<?php
$table = $session->getTable("User");

try {
    $user = $table->find(2);
} catch(Doctrine_Find_Exception $e) {
    print "Couldn't find user";
}

$user->name = "Jack Daniels";

$user->save();
?>
