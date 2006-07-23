<?php

// find all users

$coll = $session->query("FROM User");

// find all groups

$coll = $session->query("FROM Group");

// find all users and user emails

$coll = $session->query("FROM User, User.Email");

?>
