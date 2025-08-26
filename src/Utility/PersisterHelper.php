<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use BackedEnum;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\ORM\Query\QueryException;
use RuntimeException;

use function array_map;
use function array_merge;
use function is_array;
use function is_object;
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
     * @param string $fieldName
     *
     * @return array<int, string>
     *
     * @throws QueryException
     */
    public static function getTypeOfField($fieldName, ClassMetadata $class, EntityManagerInterface $em)
    {
        if (isset($class->fieldMappings[$fieldName])) {
            return [$class->fieldMappings[$fieldName]['type']];
        }

        if (! isset($class->associationMappings[$fieldName])) {
            return [];
        }

        $assoc = $class->associationMappings[$fieldName];

        if (! $assoc['isOwningSide']) {
            return self::getTypeOfField($assoc['mappedBy'], $em->getClassMetadata($assoc['targetEntity']), $em);
        }

        if ($assoc['type'] & ClassMetadata::MANY_TO_MANY) {
            $joinData = $assoc['joinTable'];
        } else {
            $joinData = $assoc;
        }

        $types       = [];
        $targetClass = $em->getClassMetadata($assoc['targetEntity']);

        foreach ($joinData['joinColumns'] as $joinColumn) {
            $types[] = self::getTypeOfColumn($joinColumn['referencedColumnName'], $targetClass, $em);
        }

        return $types;
    }

    /**
     * @param string $columnName
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public static function getTypeOfColumn($columnName, ClassMetadata $class, EntityManagerInterface $em)
    {
        if (isset($class->fieldNames[$columnName])) {
            $fieldName = $class->fieldNames[$columnName];

            if (isset($class->fieldMappings[$fieldName])) {
                return $class->fieldMappings[$fieldName]['type'];
            }
        }

        // iterate over to-one association mappings
        foreach ($class->associationMappings as $assoc) {
            if (! isset($assoc['joinColumns'])) {
                continue;
            }

            foreach ($assoc['joinColumns'] as $joinColumn) {
                if ($joinColumn['name'] === $columnName) {
                    $targetColumnName = $joinColumn['referencedColumnName'];
                    $targetClass      = $em->getClassMetadata($assoc['targetEntity']);

                    return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
                }
            }
        }

        // iterate over to-many association mappings
        foreach ($class->associationMappings as $assoc) {
            if (! (isset($assoc['joinTable']) && isset($assoc['joinTable']['joinColumns']))) {
                continue;
            }

            foreach ($assoc['joinTable']['joinColumns'] as $joinColumn) {
                if ($joinColumn['name'] === $columnName) {
                    $targetColumnName = $joinColumn['referencedColumnName'];
                    $targetClass      = $em->getClassMetadata($assoc['targetEntity']);

                    return self::getTypeOfColumn($targetColumnName, $targetClass, $em);
                }
            }
        }

        throw new RuntimeException(sprintf(
            'Could not resolve type of column "%s" of class "%s"',
            $columnName,
            $class->getName()
        ));
    }

    /**
     * Infers field types to be used by parameter type casting.
     *
     * @param mixed $value
     *
     * @return int[]|null[]|string[]
     * @phpstan-return list<int|string|null>
     *
     * @throws QueryException
     */
    public static function inferParameterTypes(string $field, $value, ClassMetadata $class, EntityManagerInterface $em): array
    {
        $types = [];

        switch (true) {
            case isset($class->fieldMappings[$field]):
                $types = array_merge($types, [$class->fieldMappings[$field]['type']]);
                break;

            case isset($class->associationMappings[$field]):
                $assoc = $class->associationMappings[$field];
                $class = $em->getClassMetadata($assoc['targetEntity']);

                if (! $assoc['isOwningSide']) {
                    $assoc = $class->associationMappings[$assoc['mappedBy']];
                    $class = $em->getClassMetadata($assoc['targetEntity']);
                }

                $columns = $assoc['type'] === ClassMetadata::MANY_TO_MANY
                    ? $assoc['relationToTargetKeyColumns']
                    : $assoc['sourceToTargetKeyColumns'];

                foreach ($columns as $column) {
                    $types[] = self::getTypeOfColumn($column, $class, $em);
                }

                break;

            default:
                $types[] = null;
                break;
        }

        if (is_array($value)) {
            return array_map(static function ($type) {
                $type = Type::getType($type);

                return $type->getBindingType() + Connection::ARRAY_PARAM_OFFSET;
            }, $types);
        }

        return $types;
    }

    /**
     * Converts a value to the type and value required to bind it as a parameter.
     *
     * @param mixed $value
     *
     * @return list<mixed>
     */
    public static function convertToParameterValue($value, EntityManagerInterface $em): array
    {
        if (is_array($value)) {
            $newValue = [];

            foreach ($value as $itemValue) {
                $newValue = array_merge($newValue, self::convertToParameterValue($itemValue, $em));
            }

            return [$newValue];
        }

        return self::convertIndividualValue($value, $em);
    }

    /**
     * @param mixed $value
     *
     * @phpstan-return list<mixed>
     */
    private static function convertIndividualValue($value, EntityManagerInterface $em): array
    {
        if (! is_object($value)) {
            return [$value];
        }

        if ($value instanceof BackedEnum) {
            return [$value->value];
        }

        $valueClass = DefaultProxyClassNameResolver::getClass($value);

        if ($em->getMetadataFactory()->isTransient($valueClass)) {
            return [$value];
        }

        $class = $em->getClassMetadata($valueClass);

        if ($class->isIdentifierComposite) {
            $newValue = [];

            foreach ($class->getIdentifierValues($value) as $innerValue) {
                $newValue = array_merge($newValue, self::convertToParameterValue($innerValue, $em));
            }

            return $newValue;
        }

        return [$em->getUnitOfWork()->getSingleIdentifierValue($value)];
    }
}
