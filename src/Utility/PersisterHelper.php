<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use BackedEnum;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Doctrine\ORM\Query\QueryException;
use RuntimeException;

use function array_map;
use function array_merge;
use function assert;
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

    /**
     * Infers field types to be used by parameter type casting.
     *
     * @return list<ParameterType|int|string>
     * @phpstan-return list<ParameterType::*|ArrayParameterType::*|string>
     *
     * @throws QueryException
     */
    public static function inferParameterTypes(
        string $field,
        mixed $value,
        ClassMetadata $class,
        EntityManagerInterface $em,
    ): array {
        $types = [];

        switch (true) {
            case isset($class->fieldMappings[$field]):
                $types = array_merge($types, [$class->fieldMappings[$field]->type]);
                break;

            case isset($class->associationMappings[$field]):
                $assoc = $em->getMetadataFactory()->getOwningSide($class->associationMappings[$field]);
                $class = $em->getClassMetadata($assoc->targetEntity);

                if ($assoc->isManyToManyOwningSide()) {
                    $columns = $assoc->relationToTargetKeyColumns;
                } else {
                    assert($assoc->isToOneOwningSide());
                    $columns = $assoc->sourceToTargetKeyColumns;
                }

                foreach ($columns as $column) {
                    $types[] = self::getTypeOfColumn($column, $class, $em);
                }

                break;

            default:
                $types[] = ParameterType::STRING;
                break;
        }

        if (is_array($value)) {
            return array_map(self::getArrayBindingType(...), $types);
        }

        return $types;
    }

    /** @phpstan-return ArrayParameterType::* */
    private static function getArrayBindingType(ParameterType|int|string $type): ArrayParameterType|int
    {
        if (! $type instanceof ParameterType) {
            $type = Type::getType((string) $type)->getBindingType();
        }

        return match ($type) {
            ParameterType::STRING => ArrayParameterType::STRING,
            ParameterType::INTEGER => ArrayParameterType::INTEGER,
            ParameterType::ASCII => ArrayParameterType::ASCII,
            ParameterType::BINARY => ArrayParameterType::BINARY,
        };
    }

    /**
     * Converts a value to the type and value required to bind it as a parameter.
     *
     * @return list<mixed>
     */
    public static function convertToParameterValue(mixed $value, EntityManagerInterface $em): array
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

    /** @phpstan-return list<mixed> */
    private static function convertIndividualValue(mixed $value, EntityManagerInterface $em): array
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
