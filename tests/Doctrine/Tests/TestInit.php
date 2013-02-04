<?php
/*
 * This file bootstraps the test environment.
 */
namespace Doctrine\Tests;

error_reporting(E_ALL | E_STRICT);

if (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    // dependencies were installed via composer - this is the main project
    $classLoader = require __DIR__ . '/../../../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../../../../autoload.php')) {
    // installed as a dependency in `vendor`
    $classLoader = require __DIR__ . '/../../../../../autoload.php';
} else {
    throw new \Exception('Can\'t find autoload.php. Did you install dependencies via composer?');
}

/* @var $classLoader \Composer\Autoload\ClassLoader */
$classLoader->add('Doctrine\\Tests\\', __DIR__ . '/../../');
unset($classLoader);

if ( ! file_exists(__DIR__ . '/Proxies') && ! mkdir(__DIR__ . '/Proxies')) {
    throw new \Exception("Could not create " . __DIR__."/Proxies Folder.");
}

if ( ! file_exists(__DIR__ . '/ORM/Proxy/generated') &&  ! mkdir(__DIR__ . '/ORM/Proxy/generated')) {
    throw new \Exception('Could not create ' . __DIR__ . '/ORM/Proxy/generated Folder.');
}
