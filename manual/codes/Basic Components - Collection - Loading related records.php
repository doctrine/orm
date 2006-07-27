<?php
$users = $session->query("FROM User");

// now lets load phonenumbers for all users

$users->loadRelated("Phonenumber");

foreach($users as $user) {
    print $user->Phonenumber->phonenumber;
    // no additional db queries needed here
}

// the loadRelated works an any relation, even associations:

$users->loadRelated("Group");

foreach($users as $user) {
    print $user->Group->name;
}
?>
