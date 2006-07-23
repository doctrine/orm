<?php
$user = new User();
$user->name = "Jack";

$group = $session->create("Group");
$group->name = "Drinking Club";

// saves all the changed objects into database

$session->flush();
?>
