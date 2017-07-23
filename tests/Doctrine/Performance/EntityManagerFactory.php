<?php

namespace Doctrine\Performance;

use Doctrine\DBAL\Driver\PDOSqlite\Driver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Tools\SchemaTool;

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
            realpath(__DIR__ . '/Models/GeoNames')
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
}
