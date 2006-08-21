<?php

// select all users

$conn->query("FROM User");

// select all users where user email is jackdaniels@drinkmore.info

$conn->query("FROM User WHERE User.Email.address = 'jackdaniels@drinkmore.info'");
?>
