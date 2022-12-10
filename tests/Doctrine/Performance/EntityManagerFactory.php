<?php

declare(strict_types=1);

namespace Doctrine\Performance;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\SQLite\Driver;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Mocks\DriverResultMock;
use Doctrine\Tests\TestUtil;

use function array_map;
use function realpath;

final class EntityManagerFactory
{
    public static function getEntityManager(array $schemaClassNames): EntityManagerInterface
    {
        $config = new Configuration();

        TestUtil::configureProxies($config);
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver([
            realpath(__DIR__ . '/Models/Cache'),
            realpath(__DIR__ . '/Models/GeoNames'),
        ]));

        $entityManager = new EntityManager(
            DriverManager::getConnection([
                'driverClass' => Driver::class,
                'memory'      => true,
            ], $config),
            $config
        );

        (new SchemaTool($entityManager))
            ->createSchema(array_map([$entityManager, 'getClassMetadata'], $schemaClassNames));

        return $entityManager;
    }

    public static function makeEntityManagerWithNoResultsConnection(): EntityManagerInterface
    {
        $config = new Configuration();

        TestUtil::configureProxies($config);
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl(ORMSetup::createDefaultAnnotationDriver([
            realpath(__DIR__ . '/Models/Cache'),
            realpath(__DIR__ . '/Models/Generic'),
            realpath(__DIR__ . '/Models/GeoNames'),
        ]));

        // A connection that doesn't really do anything
        $connection = new class ([], new Driver(), null, new EventManager()) extends Connection
        {
            /** {@inheritdoc} */
            public function executeQuery(string $sql, array $params = [], $types = [], ?QueryCacheProfile $qcp = null): Result
            {
                return new Result(new DriverResultMock(), $this);
            }
        };

        return new EntityManager($connection, $config);
    }
}
