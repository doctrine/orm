<?php
$q = new Doctrine_Query();

$q->from('User')->where('User.Phonenumber.phonenumber.contains(?,?,?)');

$users = $q->execute(array('123 123 123', '0400 999 999', '+358 100 100'));
?>
