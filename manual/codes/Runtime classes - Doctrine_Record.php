<?php
$user = $table->find(2);

// get state
$state = $user->getState();

print $user->name;

print $user["name"];

print $user->get("name");

$user->name = "Jack Daniels";

$user->set("name","Jack Daniels");

// serialize record

$serialized = serialize($user);

$user = unserialize($serialized);

// create a copy

$copy = $user->copy();

// get primary key

$id = $user->getID();

// print lots of useful info

print $user;

// save all the properties and composites
$user->save();

// delete this data access object and related objects
$user->delete();
?>
