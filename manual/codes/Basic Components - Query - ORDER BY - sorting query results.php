<?php
// find all users, sort by name descending

$coll = $conn->query("FROM User ORDER BY User.name DESC");

// find all users sort by name ascending

$coll = $conn->query("FROM User ORDER BY User.name ASC");

// or 

$coll = $conn->query("FROM User ORDER BY User.name");

// find all users and their emails, sort by email address

$coll = $conn->query("FROM User, User.Email ORDER BY User.Email.address");

// find all users and their emails, sort by user name and email address

$coll = $conn->query("FROM User, User.Email ORDER BY User.name, User.Email.address");
?>
