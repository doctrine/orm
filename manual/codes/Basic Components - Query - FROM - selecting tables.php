<?php
// find all groups

$coll = $q->from("FROM Group");

// find all users and user emails

$coll = $q->from("FROM User u LEFT JOIN u.Email e");

// find all users and user emails with only user name and
// age + email address loaded

$coll = $q->select('u.name, u.age, e.address')
          ->from('FROM User u')
          ->leftJoin('u.Email e')
          ->execute();

// find all users, user email and user phonenumbers

$coll = $q->from('FROM User u')
          ->innerJoin('u.Email e')
          ->innerJoin('u.Phonenumber p')
          ->execute();
?>
