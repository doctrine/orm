<?php
require_once("path-to-doctrine/Doctrine.php");

// autoloading objects

function __autoload($class) {
    Doctrine::autoload($class);
}

// registering an autoload function, useful when multiple
// frameworks are using __autoload()

spl_autoload_register(array('Doctrine', 'autoload'));
?>
