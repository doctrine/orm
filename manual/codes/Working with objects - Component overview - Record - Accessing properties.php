<?php
$user = $table->find(3);

// access property through overloading

$name = $user->name;

// access property with get()

$name = $user->get("name");

// access property with ArrayAccess interface

$name = $user['name'];

// iterating through properties

foreach($user as $key => $value) {

}
?>
