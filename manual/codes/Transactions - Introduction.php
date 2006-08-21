<?php
$conn->beginTransaction();

$user = new User();
$user->name = 'New user';
$user->save();

$user = $conn->getTable('User')->find(5);
$user->name = 'Modified user';
$user->save();

$conn->commit(); // all the queries are executed here
?>
