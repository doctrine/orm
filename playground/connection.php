<?php
$dbh = new PDO('sqlite::memory:');
$conn = Doctrine_Manager::connection($dbh);
$manager = Doctrine_Manager::getInstance();