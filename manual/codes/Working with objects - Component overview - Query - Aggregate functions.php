<?php
$q = new Doctrine_Query();

$q->from('User(COUNT(id))');

// returns an array
$a = $q->execute();

// selecting multiple aggregate values:
$q = new Doctrine_Query();

$q->from('User(COUNT(id)).Phonenumber(MAX(phonenumber))');

$a = $q->execute();
?>
