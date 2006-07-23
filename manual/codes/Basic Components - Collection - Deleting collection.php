<?php
// delete all users with name 'John'

$users = $table->findBySql("name LIKE '%John%'");

$users->delete();
?>
