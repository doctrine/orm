<?php
$q = new Doctrine_Query();

// find all users, sort by name descending

$users = $q->from('User u')->orderby('u.name DESC');

// find all users sort by name ascending

$users = $q->from('User u')->orderby('u.name ASC');

// find all users and their emails, sort by email address in ascending order

$users = $q->from('User u')->leftJoin('u.Email e')->orderby('e.address');

// find all users and their emails, sort by user name and email address

$users = $q->from('User u')->leftJoin('u.Email e')
           ->addOrderby('u.name')->addOrderby('e.address');
?>
