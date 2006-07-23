<?php



// find all groups where the group primary key is bigger than 10

$coll = $session->query("FROM Group WHERE Group.id > 10");

// find all users where users where user name matches a regular expression, 
// REGEXP keyword must be supported by the underlying database

$coll = $session->query("FROM User WHERE User.name REGEXP '[ad]'");

// find all users and their associated emails where SOME of the users phonenumbers 
// (the association between user and phonenumber tables is Many-To-Many) starts with 123

$coll = $session->query("FROM User, User.Email WHERE User.Phonenumber.phonenumber LIKE '123%'");

// multiple conditions

$coll = $session->query("FROM User WHERE User.name LIKE '%Jack%' && User.Email.address LIKE '%@drinkmore.info'");

// nesting conditions

$coll = $session->query("FROM User WHERE (User.name LIKE '%Jack%' || User.name LIKE '%John%') && User.Email.address LIKE '%@drinkmore.info'");

?>
