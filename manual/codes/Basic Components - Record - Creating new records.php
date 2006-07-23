<?php
$user = $session->create("User");

// alternative way:

$table = $session->getTable("User");

$user = $table->create();

// the simpliest way:

$user = new User();


// records support array access
$user["name"] = "John Locke";

// save user into database
$user->save();
?>
