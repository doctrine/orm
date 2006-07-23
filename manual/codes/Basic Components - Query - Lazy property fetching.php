<?php

// retrieve all users with only their properties id and name loaded

$users = $session->query("FROM User(id, name)");
?>
