<?php

// using PDO like dsn for connecting sqlite memory table

$dbh = Doctrine_DB::getConnection('sqlite::memory:');

// using PDO like dsn for connecting pgsql database

$dbh = Doctrine_DB::getConnection('pgsql://root:password@localhost/mydb');

// using PEAR like dsn for connecting mysql database

$dbh = Doctrine_DB::getConnection('mysql://root:password@localhost/test');
?>
