<?php

// find the first ten users and their emails

$coll = $session->query("FROM User, User.Email LIMIT 10");

// find the first ten users starting from the user number 5

$coll = $session->query("FROM User LIMIT 10 OFFSET 5");

?>
