<?php
/*
 * This file bootstraps the test environment.
 */

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once '../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new Doctrine_Common_ClassLoader();
// checking for existance should not be necessary, remove as soon as possible
$classLoader->setCheckFileExists(true);
$classLoader->register();

$modelDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models';
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'cms'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'company'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'ecommerce'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'forum'
);

// Some of these classes depend on Doctrine_* classes
require_once 'Doctrine_TestCase.php';
require_once 'Doctrine_TestUtil.php';
require_once 'Doctrine_DbalTestCase.php';
require_once 'Doctrine_OrmTestCase.php';
require_once 'Doctrine_OrmFunctionalTestCase.php';
require_once 'Doctrine_TestSuite.php';
require_once 'Doctrine_OrmTestSuite.php';
require_once 'Doctrine_OrmFunctionalTestSuite.php';
require_once 'Doctrine_DbalTestSuite.php';
