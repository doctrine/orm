<?php

require_once __DIR__ . '/../../lib/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Entities', __DIR__);
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Proxies', __DIR__);
$classLoader->register();

require_once '/var/workspaces/nexxone/library/Zend/Acl/Resource/Interface.php';
require_once '/var/workspaces/nexxone/library/Zend/Acl/Role/Interface.php';

$classLoader = new \Doctrine\Common\ClassLoader('Core\Model', '/var/workspaces/nexxone/application/modules/core/models/');
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Content\Model', '/var/workspaces/nexxone/application/modules/content/models/');
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('User\Model', '/var/workspaces/nexxone/application/modules/user/models/');
$classLoader->register();


$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

$config->setProxyDir(__DIR__ . '/Proxies');

$config->setProxyNamespace('LivandoCMSProxies');
$driver =  new Doctrine\ORM\Mapping\Driver\YamlDriver(
    array(
        '/var/workspaces/nexxone/application/modules/core/config/schema',
        '/var/workspaces/nexxone/application/modules/content/config/schema',
        '/var/workspaces/nexxone/application/modules/user/config/schema',
    )
);
#$driver = new Doctrine\ORM\Mapping\Driver\YamlDriver(array('./yaml/'), YamlDriver::PRELOAD);
$config->setMetadataDriverImpl($driver);

$conn = new \Doctrine\DBAL\Connection(
            array (
                'pdo' => new PDO(
                    "mysql:host=localhost;dbname=nexxone;unix_socket=/var/run/mysqld/mysqld.sock",
                    'root',
                    'mysql5023'
                )
            ),
            new Doctrine\DBAL\Driver\PDOMySql\Driver(),
            $config
        );

//$connectionOptions = array(
//    'driver' => 'pdo_sqlite',
//    'path' => 'database.sqlite'
//);

// These are required named variables (names can't change!)
$em = \Doctrine\ORM\EntityManager::create($conn, $config);

$helpers = array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);
