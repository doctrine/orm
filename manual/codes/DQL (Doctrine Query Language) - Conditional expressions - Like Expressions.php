<?php

// finding all users whose email ends with '@gmail.com'
$users = $conn->query("FROM User u, u.Email e WHERE e.address LIKE '%@gmail.com'");

// finding all users whose name starts with letter 'A'
$users = $conn->query("FROM User u WHERE u.name LIKE 'A%'");
?>
