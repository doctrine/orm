<?php

use Doctrine\Common\Cache\ApcCache;
use Doctrine\ORM\EntityManager;

$loader = require __DIR__ . '/../../vendor/autoload.php';

// Set up class loading. You could use different autoloaders, provided by your favorite framework,
// if you want to.
$loader->add('Entities', __DIR__);
$loader->add('Proxies', __DIR__);

$config = new \Doctrine\ORM\Configuration();

// Set up Metadata Drivers
$driverImpl = $config->newDefaultAnnotationDriver(array(__DIR__ . "/Entities"));
$config->setMetadataDriverImpl($driverImpl);

// Set up caches
$cache = new ApcCache;
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

// Proxy configuration
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

// Database connection information
$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path'   => 'database.sqlite'
);

return EntityManager::create($connectionOptions, $config);