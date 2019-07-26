<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Reflection\ReflectionService;
use Doctrine\Tests\OrmFunctionalTestCase;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function in_array;
use function strtolower;

/**
 * Common BaseClass for DatabaseDriver Tests
 */
abstract class DatabaseDriverTestCase extends OrmFunctionalTestCase
{
    protected function convertToClassMetadata(array $entityTables, array $manyTables = [])
    {
        $metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            $this->createMock(ReflectionService::class),
            $this->createMock(AbstractPlatform::class)
        );
        $sm                      = $this->em->getConnection()->getSchemaManager();
        $driver                  = new DatabaseDriver($sm);
        $driver->setTables($entityTables, $manyTables);

        $metadatas = [];

        foreach ($driver->getAllClassNames() as $className) {
            $class = $driver->loadMetadataForClass($className, null, $metadataBuildingContext);

            $metadatas[$className] = $class;
        }

        return $metadatas;
    }

    /**
     * @param  string $className
     *
     * @return ClassMetadata[]
     */
    protected function extractClassMetadata(array $classNames)
    {
        $metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            $this->createMock(ReflectionService::class),
            $this->createMock(AbstractPlatform::class)
        );
        $classNames              = array_map('strtolower', $classNames);
        $metadataList            = [];

        $sm     = $this->em->getConnection()->getSchemaManager();
        $driver = new DatabaseDriver($sm);

        foreach ($driver->getAllClassNames() as $className) {
            if (! in_array(strtolower($className), $classNames, true)) {
                continue;
            }

            $class = $driver->loadMetadataForClass($className, null, $metadataBuildingContext);

            $metadataList[$className] = $class;
        }

        if (count($metadataList) !== count($classNames)) {
            $this->fail("Have not found all classes matching the names '" . implode(', ', $classNames) . "' only tables " . implode(', ', array_keys($metadataList)));
        }

        return $metadataList;
    }
}
