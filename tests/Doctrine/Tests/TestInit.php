<?php
/*
 * This file bootstraps the test environment.
 */
namespace Doctrine\Tests;

error_reporting(E_ALL | E_STRICT);

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once __DIR__ . '/../../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine');
$classLoader->register();

if (!file_exists(__DIR__."/Proxies")) {
    if (!mkdir(__DIR__."/Proxies")) {
        throw new Exception("Could not create " . __DIR__."/Proxies Folder.");
    }
}
if (!file_exists(__DIR__."/ORM/Proxy/generated")) {
    if (!mkdir(__DIR__."/ORM/Proxy/generated")) {
        throw new Exception("Could not create " . __DIR__."/ORM/Proxy/generated Folder.");
    }
}

set_include_path(
    __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib'
    . PATH_SEPARATOR .
    get_include_path() 
);