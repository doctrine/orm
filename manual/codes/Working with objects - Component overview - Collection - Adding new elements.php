<?php
$users = $table->findAll();

print count($users); // 5

$users[5]->name = "new user 1";
$users[6]->name = "new user 2";
?>
