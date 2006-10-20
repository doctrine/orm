<?php

// retrieve all users and the phonenumber count for each user

$users = $conn->query("SELECT u.*, COUNT(p.id) count FROM User u, u.Phonenumber p GROUP BY u.id");

foreach($users as $user) {
    print $user->name . ' has ' . $user->Phonenumber->getAggregateValue('count') . ' phonenumbers';
}
?>
