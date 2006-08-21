<?php

// find the first ten users and their emails

$coll = $conn->query("FROM User, User.Email LIMIT 10");

// find the first ten users starting from the user number 5

$coll = $conn->query("FROM User LIMIT 10 OFFSET 5");

?>
