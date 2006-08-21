<?php
$user = $conn->create("User");

// alternative way:

$table = $conn->getTable("User");

$user = $table->create();

// the simpliest way:

$user = new User();


// records support array access
$user["name"] = "John Locke";

// save user into database
$user->save();
?>
