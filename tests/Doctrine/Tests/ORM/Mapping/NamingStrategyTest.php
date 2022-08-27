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

/** @group DDC-559 */
class NamingStrategyTest extends OrmTestCase
{
    private static function defaultNaming(): DefaultNamingStrategy
    {
        return new DefaultNamingStrategy();
    }

    private static function underscoreNamingLower(): UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_LOWER);
    }

    private static function underscoreNamingUpper(): UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_UPPER);
    }

    private static function numberAwareUnderscoreNamingLower(): UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_LOWER, true);
    }

    private static function numberAwareUnderscoreNamingUpper(): UnderscoreNamingStrategy
    {
        return new UnderscoreNamingStrategy(CASE_UPPER, true);
    }

    /**
     * Data Provider for NamingStrategy#classToTableName
     *
     * @return array<array{NamingStrategy, string, string}>
     */
    public static function dataClassToTableName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'SomeClassName', 'SomeClassName'],
            [self::defaultNaming(), 'SomeClassName', '\SomeClassName'],
            [self::defaultNaming(), 'Name', '\Some\Class\Name'],
            [self::defaultNaming(), 'Name2Test', '\Some\Class\Name2Test'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name', '\Name\Space\SomeClassName'],
            [self::underscoreNamingLower(), 'name', '\Some\Class\Name'],
            [self::underscoreNamingLower(), 'name2test', '\Some\Class\Name2Test'],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME', '\Name\Space\SomeClassName'],
            [self::underscoreNamingUpper(), 'NAME', '\Some\Class\Name'],
            [self::underscoreNamingUpper(), 'NAME2TEST', '\Some\Class\Name2Test'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'some_class_name', '\Name\Space\SomeClassName'],
            [self::numberAwareUnderscoreNamingLower(), 'name', '\Some\Class\Name'],
            [self::numberAwareUnderscoreNamingLower(), 'name2_test', '\Some\Class\Name2Test'],
            [self::numberAwareUnderscoreNamingLower(), 'name2test', '\Some\Class\Name2test'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_CLASS_NAME', '\Name\Space\SomeClassName'],
            [self::numberAwareUnderscoreNamingUpper(), 'NAME', '\Some\Class\Name'],
            [self::numberAwareUnderscoreNamingUpper(), 'NAME2_TEST', '\Some\Class\Name2Test'],
            [self::numberAwareUnderscoreNamingUpper(), 'NAME2TEST', '\Some\Class\Name2test'],
        ];
    }

    /** @dataProvider dataClassToTableName */
    public function testClassToTableName(NamingStrategy $strategy, string $expected, string $className): void
    {
        self::assertSame($expected, $strategy->classToTableName($className));
    }

    /**
     * Data Provider for NamingStrategy#propertyToColumnName
     *
     * @return array<array{NamingStrategy, string, string, string}>
     */
    public static function dataPropertyToColumnName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someProperty', 'someProperty', 'Some\Class'],
            [self::defaultNaming(), 'SOME_PROPERTY', 'SOME_PROPERTY', 'Some\Class'],
            [self::defaultNaming(), 'some_property', 'some_property', 'Some\Class'],
            [self::defaultNaming(), 'base64Encoded', 'base64Encoded', 'Some\Class'],
            [self::defaultNaming(), 'base64_encoded', 'base64_encoded', 'Some\Class'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_property', 'someProperty', 'Some\Class'],
            [self::underscoreNamingLower(), 'base64encoded', 'base64Encoded', 'Some\Class'],
            [self::underscoreNamingLower(), 'base64encoded', 'base64encoded', 'Some\Class'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'someProperty', 'Some\Class'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'some_property', 'Some\Class'],
            [self::underscoreNamingUpper(), 'SOME_PROPERTY', 'SOME_PROPERTY', 'Some\Class'],
            [self::underscoreNamingUpper(), 'BASE64ENCODED', 'base64Encoded', 'Some\Class'],
            [self::underscoreNamingUpper(), 'BASE64ENCODED', 'base64encoded', 'Some\Class'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'some_property', 'someProperty', 'Some\Class'],
            [self::numberAwareUnderscoreNamingLower(), 'base64_encoded', 'base64Encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingLower(), 'base64encoded', 'base64encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_PROPERTY', 'someProperty', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_PROPERTY', 'some_property', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_PROPERTY', 'SOME_PROPERTY', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'BASE64_ENCODED', 'base64Encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'BASE64ENCODED', 'base64encoded', 'Some\Class'],
        ];
    }

    /** @dataProvider dataPropertyToColumnName */
    public function testPropertyToColumnName(
        NamingStrategy $strategy,
        string $expected,
        string $propertyName,
        string $className
    ): void {
        self::assertSame($expected, $strategy->propertyToColumnName($propertyName, $className));
    }

    /**
     * Data Provider for NamingStrategy#referenceColumnName
     *
     * @return array<array{NamingStrategy, string}>
     */
    public static function dataReferenceColumnName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'id'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'id'],
            [self::underscoreNamingUpper(), 'ID'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'id'],
            [self::numberAwareUnderscoreNamingUpper(), 'ID'],
        ];
    }

    /** @dataProvider dataReferenceColumnName */
    public function testReferenceColumnName(NamingStrategy $strategy, string $expected): void
    {
        self::assertSame($expected, $strategy->referenceColumnName());
    }

    /**
     * Data Provider for NamingStrategy#joinColumnName
     *
     * @return array<array{NamingStrategy, string, string}>
     */
    public static function dataJoinColumnName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someColumn_id', 'someColumn', 'Some\Class'],
            [self::defaultNaming(), 'some_column_id', 'some_column', 'Some\Class'],
            [self::defaultNaming(), 'base64Encoded_id', 'base64Encoded', 'Some\Class'],
            [self::defaultNaming(), 'base64_encoded_id', 'base64_encoded', 'Some\Class'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_column_id', 'someColumn', 'Some\Class'],
            [self::underscoreNamingLower(), 'base64encoded_id', 'base64Encoded', 'Some\Class'],
            [self::underscoreNamingUpper(), 'SOME_COLUMN_ID', 'someColumn', 'Some\Class'],
            [self::underscoreNamingUpper(), 'BASE64ENCODED_ID', 'base64Encoded', 'Some\Class'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'some_column_id', 'someColumn', 'Some\Class'],
            [self::numberAwareUnderscoreNamingLower(), 'base64_encoded_id', 'base64Encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingLower(), 'base64encoded_id', 'base64encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_COLUMN_ID', 'someColumn', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'BASE64_ENCODED_ID', 'base64Encoded', 'Some\Class'],
            [self::numberAwareUnderscoreNamingUpper(), 'BASE64ENCODED_ID', 'base64encoded', 'Some\Class'],

            // JoinColumnClassNamingStrategy
            [new JoinColumnClassNamingStrategy(), 'classname_someColumn_id', 'someColumn', 'Some\ClassName'],
            [new JoinColumnClassNamingStrategy(), 'classname_some_column_id', 'some_column', 'ClassName'],
        ];
    }

    /**
     * @param UnderscoreNamingStrategy|DefaultNamingStrategy $strategy
     *
     * @dataProvider dataJoinColumnName
     */
    public function testJoinColumnName(
        NamingStrategy $strategy,
        string $expected,
        string $propertyName,
        ?string $className = null
    ): void {
        self::assertSame($expected, $strategy->joinColumnName($propertyName, $className));
    }

    /**
     * Data Provider for NamingStrategy#joinTableName
     *
     * @return array<array{NamingStrategy, string, string, string|null}>
     */
    public static function dataJoinTableName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someclassname_classname', 'SomeClassName', 'Some\ClassName', 'some_property'],
            [self::defaultNaming(), 'someclassname_classname', '\SomeClassName', 'ClassName', 'some_property'],
            [self::defaultNaming(), 'name_classname', '\Some\Class\Name', 'ClassName', 'some_property'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name_class_name', 'SomeClassName', 'Some\ClassName', 'some_property'],
            [self::underscoreNamingLower(), 'class1test_class2test', 'Class1Test', 'Some\Class2Test', 'some_property'],
            [self::underscoreNamingLower(), 'some_class_name_class_name', '\SomeClassName', 'ClassName', 'some_property'],
            [self::underscoreNamingLower(), 'name_class_name', '\Some\Class\Name', 'ClassName', 'some_property'],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', 'SomeClassName', 'Some\ClassName', 'some_property'],
            [self::underscoreNamingUpper(), 'CLASS1TEST_CLASS2TEST', 'Class1Test', 'Some\Class2Test', 'some_property'],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', '\SomeClassName', 'ClassName', 'some_property'],
            [self::underscoreNamingUpper(), 'NAME_CLASS_NAME', '\Some\Class\Name', 'ClassName', 'some_property'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'some_class_name_class_name', 'SomeClassName', 'Some\ClassName', 'some_property'],
            [self::numberAwareUnderscoreNamingLower(), 'class1_test_class2_test', 'Class1Test', 'Some\Class2Test', 'some_property'],
            [self::numberAwareUnderscoreNamingLower(), 'some_class_name_class_name', '\SomeClassName', 'ClassName', 'some_property'],
            [self::numberAwareUnderscoreNamingLower(), 'name_class_name', '\Some\Class\Name', 'ClassName', 'some_property'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', 'SomeClassName', 'Some\ClassName', 'some_property'],
            [self::numberAwareUnderscoreNamingUpper(), 'CLASS1_TEST_CLASS2_TEST', 'Class1Test', 'Some\Class2Test', 'some_property'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_CLASS_NAME_CLASS_NAME', '\SomeClassName', 'ClassName', 'some_property'],
            [self::numberAwareUnderscoreNamingUpper(), 'NAME_CLASS_NAME', '\Some\Class\Name', 'ClassName', 'some_property'],
        ];
    }

    /** @dataProvider dataJoinTableName */
    public function testJoinTableName(
        NamingStrategy $strategy,
        string $expected,
        string $ownerEntity,
        string $associatedEntity,
        string $propertyName
    ): void {
        self::assertSame($expected, $strategy->joinTableName($ownerEntity, $associatedEntity, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinKeyColumnName
     *
     * @return array<array{NamingStrategy, string, string, string|null}>
     */
    public static function dataJoinKeyColumnName(): array
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someclassname_id', 'SomeClassName', null],
            [self::defaultNaming(), 'name_identifier', '\Some\Class\Name', 'identifier'],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_class_name2test_id', 'SomeClassName2Test', null],
            [self::underscoreNamingLower(), 'some_class_name_id', 'SomeClassName', null],
            [self::underscoreNamingLower(), 'class_name_identifier', '\Some\Class\ClassName', 'identifier'],
            [self::underscoreNamingLower(), 'name2test_identifier', '\Some\Class\Name2Test', 'identifier'],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME_ID', 'SomeClassName', null],
            [self::underscoreNamingUpper(), 'SOME_CLASS_NAME2TEST_ID', 'SomeClassName2Test', null],
            [self::underscoreNamingUpper(), 'CLASS_NAME_IDENTIFIER', '\Some\Class\ClassName', 'IDENTIFIER'],
            [self::underscoreNamingUpper(), 'NAME2TEST_IDENTIFIER', '\Some\Class\Name2Test', 'IDENTIFIER'],

            // NumberAwareUnderscoreNamingStrategy
            [self::numberAwareUnderscoreNamingLower(), 'some_class_name2_test_id', 'SomeClassName2Test', null],
            [self::numberAwareUnderscoreNamingLower(), 'some_class_name_id', 'SomeClassName', null],
            [self::numberAwareUnderscoreNamingLower(), 'class_name_identifier', '\Some\Class\ClassName', 'identifier'],
            [self::numberAwareUnderscoreNamingLower(), 'name2_test_identifier', '\Some\Class\Name2Test', 'identifier'],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_CLASS_NAME_ID', 'SomeClassName', null],
            [self::numberAwareUnderscoreNamingUpper(), 'SOME_CLASS_NAME2_TEST_ID', 'SomeClassName2Test', null],
            [self::numberAwareUnderscoreNamingUpper(), 'CLASS_NAME_IDENTIFIER', '\Some\Class\ClassName', 'IDENTIFIER'],
            [self::numberAwareUnderscoreNamingUpper(), 'NAME2_TEST_IDENTIFIER', '\Some\Class\Name2Test', 'IDENTIFIER'],
        ];
    }

    /** @dataProvider dataJoinKeyColumnName */
    public function testJoinKeyColumnName(
        NamingStrategy $strategy,
        string $expected,
        string $propertyEntityName,
        ?string $referencedColumnName = null
    ): void {
        self::assertSame($expected, $strategy->joinKeyColumnName($propertyEntityName, $referencedColumnName));
    }
}
