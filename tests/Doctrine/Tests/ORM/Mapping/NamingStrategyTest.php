<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\Tests\ORM\Mapping\NamingStrategy\JoinColumnClassNamingStrategy;
use Doctrine\Tests\OrmTestCase;
use const CASE_LOWER;
use const CASE_UPPER;

/**
 * @group DDC-559
 */
class NamingStrategyTest extends OrmTestCase
{
    private static function defaultNaming() : DefaultNamingStrategy
    {
        return new DefaultNamingStrategy();
    }

    private static function underscoreNamingLower() : UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_LOWER);
    }

    private static function underscoreNamingUpper() : UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_UPPER);
    }

    /**
     * Data Provider for NamingStrategy#classToTableName
     *
     * @return array<NamingStrategy|string>
     */
    public static function dataClassToTableName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'SomeClassName', 'SomeClassName'],
            [self::defaultNaming(), 'SomeClassName', '\SomeClassName'],
            [self::defaultNaming(), 'Name', '\Some\Class\Name'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name', '\Name\Space\SomeClassName'],
            [self::underscoreNamingLower(), 'name', '\Some\Class\Name'],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME', '\Name\Space\SomeClassName'],
            [self::underscoreNamingUpper(), 'NAME', '\Some\Class\Name'],
        ];
    }

    /**
     * @dataProvider dataClassToTableName
     */
    public function testClassToTableName(NamingStrategy $strategy, string $expected, string $className) : void
    {
        self::assertSame($expected, $strategy->classToTableName($className));
    }

    /**
     * Data Provider for NamingStrategy#propertyToColumnName
     *
     * @return array<NamingStrategy|string>
     */
    public static function dataPropertyToColumnName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someProperty', 'someProperty'],
            [self::defaultNaming(), 'SOME_PROPERTY', 'SOME_PROPERTY'],
            [self::defaultNaming(), 'some_property', 'some_property'],
            [self::defaultNaming(), 'base64Encoded', 'base64Encoded'],
            [self::defaultNaming(), 'base64_encoded', 'base64_encoded'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_property', 'someProperty'],
            [self::underscoreNamingLower(), 'base64_encoded', 'base64Encoded'],
            [self::underscoreNamingLower(), 'base64encoded', 'base64encoded'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'someProperty'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'some_property'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'SOME_PROPERTY'],
            [self::underscoreNamingUpper(), 'BASE64_ENCODED', 'base64Encoded'],
            [self::underscoreNamingUpper(), 'BASE64ENCODED', 'base64encoded'],
        ];
    }

    /**
     * @dataProvider dataPropertyToColumnName
     */
    public function testPropertyToColumnName(NamingStrategy $strategy, string $expected, string $propertyName) : void
    {
        self::assertSame($expected, $strategy->propertyToColumnName($propertyName));
    }

    /**
     * Data Provider for NamingStrategy#referenceColumnName
     *
     * @return array<NamingStrategy|string>
     */
    public static function dataReferenceColumnName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'id'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'id'],
            [self::underscoreNamingUpper(), 'ID'],
        ];
    }

    /**
     * @dataProvider dataReferenceColumnName
     */
    public function testReferenceColumnName(NamingStrategy $strategy, string $expected) : void
    {
        self::assertSame($expected, $strategy->referenceColumnName());
    }

    /**
     * Data Provider for NamingStrategy#joinColumnName
     *
     * @return array<NamingStrategy|string|null>
     */
    public static function dataJoinColumnName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someColumn_id', 'someColumn', null],
            [self::defaultNaming(), 'some_column_id', 'some_column', null],
            [self::defaultNaming(), 'base64Encoded_id', 'base64Encoded', null],
            [self::defaultNaming(), 'base64_encoded_id', 'base64_encoded', null],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_column_id', 'someColumn', null],
            [self::underscoreNamingLower(), 'base64_encoded_id', 'base64Encoded', null],
            [self::underscoreNamingUpper(), 'SOME_COLUMN_ID', 'someColumn', null],
            [self::underscoreNamingUpper(), 'BASE64_ENCODED_ID', 'base64Encoded', null],
            // JoinColumnClassNamingStrategy
            [new JoinColumnClassNamingStrategy(), 'classname_someColumn_id', 'someColumn', 'Some\ClassName'],
            [new JoinColumnClassNamingStrategy(), 'classname_some_column_id', 'some_column', 'ClassName'],
        ];
    }

    /**
     * @dataProvider dataJoinColumnName
     */
    public function testJoinColumnName(
        NamingStrategy $strategy,
        string $expected,
        string $propertyName,
        ?string $className = null
    ) : void {
        self::assertSame($expected, $strategy->joinColumnName($propertyName, $className));
    }

    /**
     * Data Provider for NamingStrategy#joinTableName
     *
     * @return array<NamingStrategy|string|null>
     */
    public static function dataJoinTableName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someclassname_classname', 'SomeClassName', 'Some\ClassName', null],
            [self::defaultNaming(), 'someclassname_classname', '\SomeClassName', 'ClassName', null],
            [self::defaultNaming(), 'name_classname', '\Some\Class\Name', 'ClassName', null],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name_class_name', 'SomeClassName', 'Some\ClassName', null],
            [self::underscoreNamingLower(), 'some_class_name_class_name', '\SomeClassName', 'ClassName', null],
            [self::underscoreNamingLower(), 'name_class_name', '\Some\Class\Name', 'ClassName', null],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', 'SomeClassName', 'Some\ClassName', null],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', '\SomeClassName', 'ClassName', null],
            [self::underscoreNamingUpper(), 'NAME_CLASS_NAME', '\Some\Class\Name', 'ClassName', null],
        ];
    }

    /**
     * @dataProvider dataJoinTableName
     */
    public function testJoinTableName(
        NamingStrategy $strategy,
        string $expected,
        string $ownerEntity,
        string $associatedEntity,
        ?string $propertyName = null
    ) : void {
        self::assertSame($expected, $strategy->joinTableName($ownerEntity, $associatedEntity, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinKeyColumnName
     *
     * @return array<NamingStrategy|string|null>
     */
    public static function dataJoinKeyColumnName() : array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someclassname_id', 'SomeClassName', null, null],
            [self::defaultNaming(), 'name_identifier', '\Some\Class\Name', 'identifier', null],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name_id', 'SomeClassName', null, null],
            [self::underscoreNamingLower(), 'class_name_identifier', '\Some\Class\ClassName', 'identifier', null],

            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_ID', 'SomeClassName', null, null],
            [self::underscoreNamingUpper(), 'CLASS_NAME_IDENTIFIER', '\Some\Class\ClassName', 'IDENTIFIER', null],
        ];
    }

    /**
     * @dataProvider dataJoinKeyColumnName
     */
    public function testJoinKeyColumnName(
        NamingStrategy $strategy,
        string $expected,
        string $propertyEntityName,
        ?string $referencedColumnName = null,
        ?string $propertyName = null
    ) : void {
        self::assertSame($expected, $strategy->joinKeyColumnName($propertyEntityName, $referencedColumnName, $propertyName));
    }
}
