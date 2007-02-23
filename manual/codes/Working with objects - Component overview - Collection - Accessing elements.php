<?php
$table = $conn->getTable("User");

$users = $table->findAll();

// accessing elements with ArrayAccess interface

$users[0]->name = "Jack Daniels";

$users[1]->name = "John Locke";

// accessing elements with get()

print $users->get(1)->name;
?>
