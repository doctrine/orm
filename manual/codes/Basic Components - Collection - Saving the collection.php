<?php
$users = $table->findAll();

$users[0]->name = "Jack Daniels";

$users[1]->name = "John Locke";

$users->save();
?>
