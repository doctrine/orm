<?php

require '../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();
$classLoader->setBasePath('Doctrine', realpath(__DIR__ . '/../../lib'));
$classLoader->setBasePath('Entities', __DIR__);

$config = new \Doctrine\ORM\Configuration;
$cache = new \Doctrine\Common\Cache\ArrayCache;
// $cache = new \Doctrine\Common\Cache\ApcCache; # RECOMMENDED FOR PRODUCTION
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);

# EXAMPLE FOR YAML DRIVER
#$config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\YamlDriver(__DIR__ . '/yaml'));

# EXAMPLE FOR XML DRIVER
#$config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\YamlDriver(__DIR__ . '/xml'));

$eventManager = new \Doctrine\Common\EventManager();
$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
);
$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config, $eventManager);