<?php
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'Doctrine_TestCase.php';
require_once 'Doctrine_TestSuite.php';

require_once '../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));