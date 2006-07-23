<?php
require_once("path-to-doctrine/Doctrine.class.php");

// autoloading objects

function __autoload($class) {
    Doctrine::autoload($class);
}

// loading all components

Doctrine::loadAll();
?>
