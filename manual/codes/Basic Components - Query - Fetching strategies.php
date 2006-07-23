<?php
// select all users and load the data directly (Immediate fetching strategy)

$coll = $session->query("FROM User-I");

// or

$coll = $session->query("FROM User-IMMEDIATE");

// select all users and load the data in batches

$coll = $session->query("FROM User-B");

// or 

$coll = $session->query("FROM User-BATCH");

// select all user and use lazy fetching

$coll = $session->query("FROM User-L");

// or 

$coll = $session->query("FROM User-LAZY");
?>
