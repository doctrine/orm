<?php
$query->from('User u')->innerJoin('u.Email e');

$query->execute();

// executed SQL query:
// SELECT ... FROM user INNER JOIN email ON ...

$query->from('User u')->leftJoin('u.Email e');

$query->execute();

// executed SQL query:
// SELECT ... FROM user LEFT JOIN email ON ...
?>
