<?php

// using PDO dsn for connecting sqlite memory table

$dbh = Doctrine_DB::getConnection('sqlite::memory:');

// using PEAR like dsn for connecting mysql database

$dsn = 'mysql://root:password@localhost/test';
$dbh = Doctrine_DB::getConnection($dsn);
?>
