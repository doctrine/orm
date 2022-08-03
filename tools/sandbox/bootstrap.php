<?php

declare(strict_types=1);

use Doctrine\Common\Cache;
use Doctrine\ORM\Cache\CacheConfiguration;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Logging\StatisticsCacheLogger;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

// Path to composer autoloader. You can use different provided by your favorite framework,
// if you want to.
$loaderPath = __DIR__ . '/../../vendor/autoload.php';
if (! is_readable($loaderPath)) {
    throw new LogicException('Run php composer.phar install at first');
}

$loader = require $loaderPath;

// Set up class loading.
$loader->add('Entities', __DIR__);
$loader->add('Proxies', __DIR__);

$debug  = true;
$config = new Configuration();

// Set up Metadata Drivers
$driverImpl = $config->newDefaultAnnotationDriver([__DIR__ . '/Entities']);
$config->setMetadataDriverImpl($driverImpl);

// Set up caches, depending on $debug variable.
// You can use another variable to define which one of the cache systems you gonna use.
$cache = $debug ? new Cache\ArrayCache() : new Cache\ApcCache();
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

// Proxy configuration
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

// Database connection information
$connectionOptions = [
    'driver' => 'pdo_sqlite',
    'path'   => 'database.sqlite',
];

// Enable second-level cache
$cacheConfig = new CacheConfiguration();
$cacheDriver = $debug ? new Cache\ArrayCache() : new Cache\ApcCache();
$cacheLogger = new StatisticsCacheLogger();
$factory     = new DefaultCacheFactory($cacheConfig->getRegionsConfiguration(), $cacheDriver);

if ($debug) {
    $cacheConfig->setCacheLogger($cacheLogger);
}

$cacheConfig->setCacheFactory($factory);
$config->setSecondLevelCacheEnabled(true);
$config->setSecondLevelCacheConfiguration($cacheConfig);

return EntityManager::create($connectionOptions, $config);
