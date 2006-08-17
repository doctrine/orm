<?php
$table = $session->getTable("User");


$user = $table->find(2);

if($user !== false) {
    $user->name = "Jack Daniels";
    
    $user->save();
}
?>
