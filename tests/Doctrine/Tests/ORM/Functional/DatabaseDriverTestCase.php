<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Common BaseClass for DatabaseDriver Tests
 */
abstract class DatabaseDriverTestCase extends OrmFunctionalTestCase
{
    protected function convertToClassMetadata(array $entityTables, array $manyTables = array())
    {
        $sm = $this->_em->getConnection()->getSchemaManager();
        $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($sm);
        $driver->setTables($entityTables, $manyTables);

        $metadatas = array();
        foreach ($driver->getAllClassNames() AS $className) {
            $class = new ClassMetadataInfo($className);
            $driver->loadMetadataForClass($className, $class);
            $metadatas[$className] = $class;
        }

        return $metadatas;
    }

    /**
     * @param  string $className
     * @return ClassMetadata
     */
    protected function extractClassMetadata(array $classNames)
    {
        $classNames = array_map('strtolower', $classNames);
        $metadatas = array();

        $sm = $this->_em->getConnection()->getSchemaManager();
        $driver = new \Doctrine\ORM\Mapping\Driver\DatabaseDriver($sm);

        foreach ($driver->getAllClassNames() as $className) {
            if (!in_array(strtolower($className), $classNames)) {
                continue;
            }
            $class = new ClassMetadataInfo($className);
            $driver->loadMetadataForClass($className, $class);
            $metadatas[$className] = $class;
        }

        if (count($metadatas) != count($classNames)) {
            $this->fail("Have not found all classes matching the names '" . implode(", ", $classNames) . "' only tables " . implode(", ", array_keys($metadatas)));
        }
        return $metadatas;
    }
}
