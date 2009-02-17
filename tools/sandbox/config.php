<?php
require '../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader();
$classLoader->register();

$classLoader->setBasePath('Doctrine', realpath(__DIR__ . '/../../lib'));
$classLoader->setBasePath('Entities', __DIR__);

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\ORM\Cache\ArrayCache);
$config->setMetadataDriverImpl(new \Doctrine\ORM\Mapping\Driver\YamlDriver(__DIR__ . '/schema'));
$eventManager = new \Doctrine\Common\EventManager();
$connectionOptions = array(
    'driver' => 'pdo_sqlite',
    'path' => 'database.sqlite'
);
$em = \Doctrine\ORM\EntityManager::create($connectionOptions, 'doctrine', $config, $eventManager);