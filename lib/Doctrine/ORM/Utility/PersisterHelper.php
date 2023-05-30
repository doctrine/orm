<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\QueryException;
use RuntimeException;

use function sprintf;

/**
 * The PersisterHelper contains logic to infer binding types which is used in
 * several persisters.
 *
 * @link   www.doctrine-project.org
 */
class PersisterHelper
{
    /**
     * @return list<string>
     *
     * @throws QueryException
     */
    public static function getTypeOfField(string $fieldName, ClassMetadata $class, EntityManagerInterface $em): array
    {
        if (isset($class->fieldMappings[$fieldName])) {
            return [$class->fieldMappings[$fieldName]->type];
        }

        if (! isset($class->associationMappings[$fieldName])) {
            return [];
        }

        $assoc = $class->associationMappings[$fieldName];

        if (! $assoc->isOwningSide()) {
            return self::getTypeOfField($assoc->mappedBy, $em->getClassMetadata($assoc->targetEntity), $em);
        }

        if ($assoc->isManyToManyOwningSide()) {
            $joinData = $assoc->joinTable;
        } else {
            $joinData = $assoc;
        }

        $types       = [];
        $targetClass = $em->getClassMetadata($assoc->targetEntity);

        foreach ($joinData->joinColumns as $joinColumn) {
            $types[] = self::getTypeOfColumn($joinColumn->referencedColumnName, $targetClass, $em);
        }

        return $types;
    }

    /** @throws RuntimeException */
    public static function getTypeOfColumn(string $columnName, ClassMetadata $class, EntityManagerInterface $em): string
    {
        if (isset($class->fieldNames[$columnName])) {
            $fieldName = $class->fieldNames[$columnName];

            if (isset($class->fieldMappings[$fieldName])) {
                return $class->fieldMappings[$fieldName]->type;
            }
        }

        // iterate over to-one association mappings
        foreach ($class->associationMappings as $assoc) {
            if (! $assoc->isToOneOwningSide()) {
                continue;
            }

            foreach ($assoc->joinColumns as $joinColumn) {
                if ($joinColumn->name === $columnName) {
                    $targetColumnName = $joinColumn->referencedColumnName;
                    $targetClass      = $em->getClassMetadata($assoc->targetEntity);

                    return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
                }
            }
        }

        // iterate over to-many association mappings
        foreach ($class->associationMappings as $assoc) {
            if (! $assoc->isManyToManyOwningSide()) {
                continue;
            }

            foreach ($assoc->joinTable->joinColumns as $joinColumn) {
                if ($joinColumn->name === $columnName) {
                    $targetColumnName = $joinColumn->referencedColumnName;
                    $targetClass      = $em->getClassMetadata($assoc->targetEntity);

                    return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
                }
            }
        }

        throw new RuntimeException(sprintf(
            'Could not resolve type of column "%s" of class "%s"',
            $columnName,
            $class->getName(),
        ));
    }
}
