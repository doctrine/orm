<?php
$session->beginTransaction();

$user = new User();
$user->name = 'New user';
$user->save();

$user = $session->getTable('User')->find(5);
$user->name = 'Modified user';
$user->save();


$pending = $session->getInserts(); // an array containing one element

$pending = $session->getUpdates(); // an array containing one element

$session->commit(); // all the queries are executed here
?>
