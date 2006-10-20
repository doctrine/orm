<?php

// retrieve the first 20 users and all their associated phonenumbers

$users = $conn->query("SELECT u.*, p.* FROM User u, u.Phonenumber p LIMIT 20");

foreach($users as $user) {
    print ' --- '.$user->name.' --- \n';

    foreach($user->Phonenumber as $p) {
        print $p->phonenumber.'\n';
    }
}
?>
