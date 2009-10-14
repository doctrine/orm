<?php
/**
 * Welcome to Doctrine 2.
 * 
 * This is the index file of the sandbox. The first section of this file
 * demonstrates the bootstrapping and configuration procedure of Doctrine 2.
 * Below that section you can place your test code and experiment.
 */

namespace Sandbox;

use Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\Common\Cache\ApcCache,
    Entities\User, Entities\Address;

require '../../lib/Doctrine/Common/GlobalClassLoader.php';

// Set up class loading, we could alternatively use several IsolatedClassLoaders.
// You could also use different autoloaders, provided by your favorite framework.
$classLoader = new \Doctrine\Common\GlobalClassLoader();
$classLoader->registerNamespace('Doctrine', realpath(__DIR__ . '/../../lib'));
$classLoader->registerNamespace('Entities', __DIR__);
$classLoader->registerNamespace('Proxies', __DIR__);
$classLoader->register();

// Set up caches
$config = new Configuration;
$cache = new ApcCache;
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Proxy configuration
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

// Database connection information
$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
);

// Create EntityManager
$em = EntityManager::create($connectionOptions, $config);

## PUT YOUR TEST CODE BELOW

$user = new User;
$address = new Address;

echo 'Hello World!' . PHP_EOL;