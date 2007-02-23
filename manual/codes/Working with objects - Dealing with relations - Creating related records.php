<?php
// NOTE: related record have always the first letter in uppercase
$email = $user->Email;

$email->address = "jackdaniels@drinkmore.info";

$user->save();

// alternative:

$user->Email->address = "jackdaniels@drinkmore.info";

$user->save();
?>
