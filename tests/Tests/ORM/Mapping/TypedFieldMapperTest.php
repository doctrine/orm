<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ChainTypedFieldMapper;
use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;
use Doctrine\ORM\Mapping\TypedFieldMapper;
use Doctrine\Tests\Models\TypedProperties\UserTyped;
use Doctrine\Tests\ORM\Mapping\TypedFieldMapper\CustomIntAsStringTypedFieldMapper;
use Doctrine\Tests\OrmTestCase;
use ReflectionClass;

/**
 * @group GH10313
 * @requires PHP 7.4
 */
class TypedFieldMapperTest extends OrmTestCase
{
    private static function defaultTypedFieldMapper(): DefaultTypedFieldMapper
    {
        return new DefaultTypedFieldMapper();
    }

    private static function customTypedFieldMapper(): CustomIntAsStringTypedFieldMapper
    {
        return new CustomIntAsStringTypedFieldMapper();
    }

    private static function chainTypedFieldMapper(): ChainTypedFieldMapper
    {
        return new ChainTypedFieldMapper(self::customTypedFieldMapper(), self::defaultTypedFieldMapper());
    }

    /**
     * Data Provider for NamingStrategy#classToTableName
     *
     * @return array<
     *     array{
     *         TypedFieldMapper,
     *         ReflectionClass,
     *         array{fieldName: string, enumType?: string, type?: mixed},
     *         array{fieldName: string, enumType?: string, type?: mixed}
     *     }>
     */
    public static function dataFieldToMappedField(): array
    {
        $reflectionClass = new ReflectionClass(UserTyped::class);

        return [
            // DefaultTypedFieldMapper
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'id'], ['fieldName' => 'id', 'type' => Types::INTEGER]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'username'], ['fieldName' => 'username', 'type' => Types::STRING]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'dateInterval'], ['fieldName' => 'dateInterval', 'type' => Types::DATEINTERVAL]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'dateTime'], ['fieldName' => 'dateTime', 'type' => Types::DATETIME_MUTABLE]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'dateTimeImmutable'], ['fieldName' => 'dateTimeImmutable', 'type' => Types::DATETIME_IMMUTABLE]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'array'], ['fieldName' => 'array', 'type' => Types::JSON]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'boolean'], ['fieldName' => 'boolean', 'type' => Types::BOOLEAN]],
            [self::defaultTypedFieldMapper(), $reflectionClass, ['fieldName' => 'float'], ['fieldName' => 'float', 'type' => Types::FLOAT]],

            // CustomIntAsStringTypedFieldMapper
            [self::customTypedFieldMapper(), $reflectionClass, ['fieldName' => 'id'], ['fieldName' => 'id', 'type' => Types::STRING]],

            // ChainTypedFieldMapper
            [self::chainTypedFieldMapper(), $reflectionClass, ['fieldName' => 'id'], ['fieldName' => 'id', 'type' => Types::STRING]],
            [self::chainTypedFieldMapper(), $reflectionClass, ['fieldName' => 'username'], ['fieldName' => 'username', 'type' => Types::STRING]],
        ];
    }

    /**
     * @param array{fieldName: string, enumType?: string, type?: mixed} $mapping
     * @param array{fieldName: string, enumType?: string, type?: mixed} $finalMapping
     *
     * @dataProvider dataFieldToMappedField
     */
    public function testValidateAndComplete(
        TypedFieldMapper $typedFieldMapper,
        ReflectionClass $reflectionClass,
        array $mapping,
        array $finalMapping
    ): void {
        self::assertSame($finalMapping, $typedFieldMapper->validateAndComplete($mapping, $reflectionClass->getProperty($mapping['fieldName'])));
    }
}
