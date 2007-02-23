<?php
// delete all users with name 'John'

$users = $table->findByDql("name LIKE '%John%'");

$users->delete();
?>
