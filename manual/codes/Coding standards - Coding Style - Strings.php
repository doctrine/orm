<?php
// literal string
$string = 'something';

// string contains apostrophes
$sql = "SELECT id, name FROM people WHERE name = 'Fred' OR name = 'Susan'";

// variable substitution
$greeting = "Hello $name, welcome back!";

// concatenation
$framework = 'Doctrine' . ' ORM ' . 'Framework';

// concatenation line breaking

$sql = "SELECT id, name FROM user "
     . "WHERE name = ? "
     . "ORDER BY name ASC";
