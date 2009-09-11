<?php

namespace Sandbox;

use Entities\User, Entities\Address;

require '../../lib/Doctrine/Common/GlobalClassLoader.php';

// Set up class loading, we could alternatively use 2 IsolatedClassLoaders
$classLoader = new \Doctrine\Common\GlobalClassLoader();
$classLoader->registerNamespace('Doctrine', realpath(__DIR__ . '/../../lib'));
$classLoader->registerNamespace('Entities', __DIR__);
$classLoader->register();

// Set up caches
$config = new \Doctrine\ORM\Configuration;
$cache = new \Doctrine\Common\Cache\ApcCache;
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Database connection information
$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
);

// Create EntityManager
$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

## PUT YOUR TEST CODE BELOW

$user = new User;
$address = new Address;

echo "Hello World!";