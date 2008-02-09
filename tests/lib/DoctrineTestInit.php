<?php
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'Doctrine_TestCase.php';
require_once 'Doctrine_DbalTestCase.php';
require_once 'Doctrine_OrmTestCase.php';
require_once 'Doctrine_TestSuite.php';
require_once 'Doctrine_OrmTestSuite.php';
require_once 'Doctrine_DbalTestSuite.php';

require_once '../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));