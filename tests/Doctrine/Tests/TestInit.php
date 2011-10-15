<?php
/*
 * This file bootstraps the test environment.
 */
namespace Doctrine\Tests;

error_reporting(E_ALL | E_STRICT);

require_once __DIR__ . '/../../../lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

if (isset($GLOBALS['DOCTRINE_COMMON_PATH'])) {
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', $GLOBALS['DOCTRINE_COMMON_PATH']);
} else {
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/vendor/doctrine-common/lib');
}
$classLoader->register();

if (isset($GLOBALS['DOCTRINE_DBAL_PATH'])) {
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', $GLOBALS['DOCTRINE_DBAL_PATH']);
} else {
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/vendor/doctrine-dbal/lib');
}
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\ORM', __DIR__ . '/../../../lib');
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Tests', __DIR__ . '/../../');
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Symfony', __DIR__ . "/../../../lib/vendor");
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
