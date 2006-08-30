<?php
$coll = new Doctrine_Collection('User');

$coll[0]->name = 'Arnold';

$coll[1]->name = 'Somebody';

$coll->save();
?>
