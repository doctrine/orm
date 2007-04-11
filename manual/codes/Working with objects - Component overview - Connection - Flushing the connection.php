<?php
$user = new User();
$user->name = 'Jack';

$group = $conn->create('Group');
$group->name = 'Drinking Club';

// saves all the changed objects into database

$conn->flush();
?>
