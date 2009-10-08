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
$cache = new \Doctrine\Common\Cache\ArrayCache;
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
$user->setName('jwage');

$address = new Address;
$address->setStreet('6512 Mercomatic Court');
$address->setUser($user);
$user->setAddress($address);

$em->persist($user);
$em->persist($address);
$em->flush();