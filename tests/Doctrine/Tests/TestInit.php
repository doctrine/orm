<?php
/*
 * This file bootstraps the test environment.
 */
namespace Doctrine\Tests;

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once '../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();
// checking for existance should not be necessary, remove as soon as possible
//$classLoader->setCheckFileExists(true);
$classLoader->register();

$modelDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models';
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'cms'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'company'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'ecommerce'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'forum'
);

// Some of these classes depend on Doctrine_* classes
/*require_once 'DoctrineTestCase.php';
require_once 'TestUtil.php';
require_once 'DbalTestCase.php';
require_once 'OrmTestCase.php';
require_once 'OrmFunctionalTestCase.php';
require_once 'DoctrineTestSuite.php';
require_once 'OrmTestSuite.php';
require_once 'OrmFunctionalTestSuite.php';
require_once 'DbalTestSuite.php';*/
