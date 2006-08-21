<?php

// find all users

$coll = $conn->query("FROM User");

// find all users with only their names (and primary keys) fetched

$coll = $conn->query("FROM User(name)");

// find all groups

$coll = $conn->query("FROM Group");

// find all users and user emails

$coll = $conn->query("FROM User.Email");

// find all users and user emails with only user name and 
// age + email address loaded

$coll = $conn->query("FROM User(name, age).Email(address)");

// find all users, user email and user phonenumbers

$coll = $conn->query("FROM User.Email, User.Phonenumber");
?>
