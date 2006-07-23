<?php
$query->from("User:Email");

$query->execute();

// executed SQL query:
// SELECT ... FROM user INNER JOIN email ON ...

$query->from("User.Email");

$query->execute();

// executed SQL query:
// SELECT ... FROM user LEFT JOIN email ON ...
?>
