<?php

declare(strict_types=1);

namespace Doctrine\Performance;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\ArrayStatement;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use function array_map;
use function realpath;

final class EntityManagerFactory
{
    public static function getEntityManager(array $schemaClassNames) : EntityManagerInterface
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../Tests/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([
            realpath(__DIR__ . '/Models/Cache'),
            realpath(__DIR__ . '/Models/GeoNames'),
        ], true));

        $entityManager = EntityManager::create(
            [
                'driverClass' => Driver::class,
                'memory'      => true,
            ],
            $config
        );

        (new SchemaTool($entityManager))
            ->createSchema(array_map([$entityManager, 'getClassMetadata'], $schemaClassNames));

        return $entityManager;
    }

    public static function makeEntityManagerWithNoResultsConnection() : EntityManagerInterface
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../Tests/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver([
            realpath(__DIR__ . '/Models/Cache'),
            realpath(__DIR__ . '/Models/Generic'),
            realpath(__DIR__ . '/Models/GeoNames'),
        ], true));

        // A connection that doesn't really do anything
        $connection = new class ([], new Driver(), null, new EventManager()) extends Connection
        {
            /** {@inheritdoc} */
            public function executeQuery($query, array $params = [], $types = [], ?QueryCacheProfile $qcp = null)
            {
                return new ArrayStatement([]);
            }
        };

        return EntityManager::create($connection, $config);
    }
}
