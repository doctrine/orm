<?php
$user->Email->delete();

$user->Phonenumber[3]->delete();

// deleting user and all related objects:

$user->delete();
?>
