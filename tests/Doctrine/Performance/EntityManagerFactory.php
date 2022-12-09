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
use Doctrine\Persistence\Reflection\RuntimeReflectionProperty;
use Doctrine\Tests\Mocks\DriverResultMock;
use Symfony\Component\VarExporter\LazyGhostTrait;

use function array_map;
use function class_exists;
use function realpath;
use function trait_exists;

final class EntityManagerFactory
{
    public static function getEntityManager(array $schemaClassNames): EntityManagerInterface
    {
        $config = new Configuration();

        $config->setLazyGhostObjectEnabled(trait_exists(LazyGhostTrait::class) && class_exists(RuntimeReflectionProperty::class));
        $config->setProxyDir(__DIR__ . '/../Tests/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
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

        $config->setLazyGhostObjectEnabled(trait_exists(LazyGhostTrait::class) && class_exists(RuntimeReflectionProperty::class));
        $config->setProxyDir(__DIR__ . '/../Tests/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');
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
