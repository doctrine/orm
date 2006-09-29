<?php
// DO NOT USE THE FOLLOWING CODE 
// (using many sql queries for object population):

$users = $conn->getTable('User')->findAll();

foreach($users as $user) {
    print $user->name."<br \>";
    foreach($user->Phonenumber as $phonenumber) {
        print $phonenumber."<br \>";
    }
}

// same thing implemented much more efficiently: 
// (using only one sql query for object population)

$users = $conn->query("FROM User.Phonenumber");

foreach($users as $user) {
    print $user->name."<br \>";
    foreach($user->Phonenumber as $phonenumber) {
        print $phonenumber."<br \>";
    }
}

?>
