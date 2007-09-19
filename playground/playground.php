<?php
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'doctrine/Doctrine.php';

spl_autoload_register(array('Doctrine', 'autoload'));

error_reporting(E_ALL | E_STRICT);