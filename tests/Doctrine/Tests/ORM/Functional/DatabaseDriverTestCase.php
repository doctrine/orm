<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
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
    /** @psalm-return array<string, ClassMetadata> */
    protected function convertToClassMetadata(array $entityTables, array $manyTables = []): array
    {
        $sm     = $this->createSchemaManager();
        $driver = new DatabaseDriver($sm);
        $driver->setTables($entityTables, $manyTables);

        $metadatas = [];
        foreach ($driver->getAllClassNames() as $className) {
            $class = new ClassMetadata($className);
            $driver->loadMetadataForClass($className, $class);
            $metadatas[$className] = $class;
        }

        return $metadatas;
    }

    /**
     * @param string[] $classNames
     *
     * @psalm-return array<class-string, ClassMetadata>
     */
    protected function extractClassMetadata(array $classNames): array
    {
        $classNames = array_map('strtolower', $classNames);
        $metadatas  = [];

        $sm     = $this->createSchemaManager();
        $driver = new DatabaseDriver($sm);

        foreach ($driver->getAllClassNames() as $className) {
            if (! in_array(strtolower($className), $classNames, true)) {
                continue;
            }

            $class = new ClassMetadata($className);
            $driver->loadMetadataForClass($className, $class);
            $metadatas[$className] = $class;
        }

        if (count($metadatas) !== count($classNames)) {
            self::fail("Have not found all classes matching the names '" . implode(', ', $classNames) . "' only tables " . implode(', ', array_keys($metadatas)));
        }

        return $metadatas;
    }
}
