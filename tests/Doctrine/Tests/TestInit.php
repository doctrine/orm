<?php
/*
 * This file bootstraps the test environment.
 */
namespace Doctrine\Tests;

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once '../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();

$modelDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models';
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'cms'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'company'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'ecommerce'
    . PATH_SEPARATOR . $modelDir . DIRECTORY_SEPARATOR . 'forum'
);

