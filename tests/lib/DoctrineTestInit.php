<?php
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once '../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

// Some of these classes depends on Doctrine_* classes
require_once 'Doctrine_TestCase.php';
require_once 'Doctrine_TestUtil.php';
require_once 'Doctrine_DbalTestCase.php';
require_once 'Doctrine_OrmTestCase.php';
require_once 'Doctrine_OrmFunctionalTestCase.php';
require_once 'Doctrine_TestSuite.php';
require_once 'Doctrine_OrmTestSuite.php';
require_once 'Doctrine_OrmFunctionalTestSuite.php';
require_once 'Doctrine_DbalTestSuite.php';

$modelDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models';
Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE);
Doctrine::loadModels($modelDir);

/*
//spl_autoload_register(array('Doctrine_TestUtil', 'autoload'));

$modelDir = dirname(__FILE__)
        . DIRECTORY_SEPARATOR . '..'
        . DIRECTORY_SEPARATOR . 'models'
        . DIRECTORY_SEPARATOR;
        
set_include_path(
        get_include_path()
        . PATH_SEPARATOR . $modelDir . 'cms'
        . PATH_SEPARATOR . $modelDir . 'ecommerce'
        . PATH_SEPARATOR . $modelDir . 'forum');
*/