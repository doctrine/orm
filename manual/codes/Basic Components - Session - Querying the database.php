<?php

// select all users

$session->query("FROM User");

// select all users where user email is jackdaniels@drinkmore.info

$session->query("FROM User WHERE User.Email.address = 'jackdaniels@drinkmore.info'");
?>
