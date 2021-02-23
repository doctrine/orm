<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Mapping\Factory\UnderscoreNamingStrategy;
use Doctrine\Tests\ORM\Mapping\NamingStrategy\JoinColumnClassNamingStrategy;
use Doctrine\Tests\OrmTestCase;
use const CASE_LOWER;
use const CASE_UPPER;

/**
 * @group DDC-559
 */
class NamingStrategyTest extends OrmTestCase
{
    /**
     * @return DefaultNamingStrategy
     */
    private static function defaultNaming()
    {
        return new DefaultNamingStrategy();
    }

    /**
     * @return UnderscoreNamingStrategy
     */
    private static function underscoreNamingLower()
    {
        return new UnderscoreNamingStrategy(CASE_LOWER);
    }

    /**
     * @return UnderscoreNamingStrategy
     */
    private static function underscoreNamingUpper()
    {
        return new UnderscoreNamingStrategy(CASE_UPPER);
    }

    /**
     * Data Provider for NamingStrategy#classToTableName
     *
     * @return array
     */
    public static function dataClassToTableName()
    {
        return [
            // DefaultNamingStrategy
            [
                self::defaultNaming(),
                'SomeClassName',
                'SomeClassName',
            ],
            [
                self::defaultNaming(),
                'SomeClassName',
                '\SomeClassName',
            ],
            [
                self::defaultNaming(),
                'Name',
                '\Some\Class\Name',
            ],

            // UnderscoreNamingStrategy
            [
                self::underscoreNamingLower(),
                'some_class_name',
                '\Name\Space\SomeClassName',
            ],
            [
                self::underscoreNamingLower(),
                'name',
                '\Some\Class\Name',
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_CLASS_NAME',
                '\Name\Space\SomeClassName',
            ],
            [
                self::underscoreNamingUpper(),
                'NAME',
                '\Some\Class\Name',
            ],
        ];
    }

    /**
     * @dataProvider dataClassToTableName
     */
    public function testClassToTableName(NamingStrategy $strategy, $expected, $className) : void
    {
        self::assertEquals($expected, $strategy->classToTableName($className));
    }

    /**
     * Data Provider for NamingStrategy#propertyToColumnName
     *
     * @return array
     */
    public static function dataPropertyToColumnName()
    {
        return [
            // DefaultNamingStrategy
            [
                self::defaultNaming(),
                'someProperty',
                'someProperty',
            ],
            [
                self::defaultNaming(),
                'SOME_PROPERTY',
                'SOME_PROPERTY',
            ],
            [
                self::defaultNaming(),
                'some_property',
                'some_property',
            ],

            // UnderscoreNamingStrategy
            [
                self::underscoreNamingLower(),
                'some_property',
                'someProperty',
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'someProperty',
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'some_property',
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'SOME_PROPERTY',
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $propertyName
     *
     * @dataProvider dataPropertyToColumnName
     */
    public function testPropertyToColumnName(NamingStrategy $strategy, $expected, $propertyName) : void
    {
        self::assertEquals($expected, $strategy->propertyToColumnName($propertyName));
    }

    /**
     * Data Provider for NamingStrategy#referenceColumnName
     *
     * @return array
     */
    public static function dataReferenceColumnName()
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
     * @param string $expected
     *
     * @dataProvider dataReferenceColumnName
     */
    public function testReferenceColumnName(NamingStrategy $strategy, $expected) : void
    {
        self::assertEquals($expected, $strategy->referenceColumnName());
    }

    /**
     * Data Provider for NamingStrategy#joinColumnName
     *
     * @return array
     */
    public static function dataJoinColumnName()
    {
        return [
            // DefaultNamingStrategy
            [self::defaultNaming(), 'someColumn_id', 'someColumn', null],
            [self::defaultNaming(), 'some_column_id', 'some_column', null],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'some_column_id', 'someColumn', null],
            [self::underscoreNamingUpper(), 'SOME_COLUMN_ID', 'someColumn', null],
            // JoinColumnClassNamingStrategy
            [new JoinColumnClassNamingStrategy(), 'classname_someColumn_id', 'someColumn', 'Some\ClassName'],
            [new JoinColumnClassNamingStrategy(), 'classname_some_column_id', 'some_column', 'ClassName'],
        ];
    }

    /**
     * @param string $expected
     * @param string $propertyName
     *
     * @dataProvider dataJoinColumnName
     */
    public function testJoinColumnName(NamingStrategy $strategy, $expected, $propertyName, $className = null) : void
    {
        self::assertEquals($expected, $strategy->joinColumnName($propertyName, $className));
    }

    /**
     * Data Provider for NamingStrategy#joinTableName
     *
     * @return array
     */
    public static function dataJoinTableName()
    {
        return [
            // DefaultNamingStrategy
            [
                self::defaultNaming(),
                'someclassname_classname',
                'SomeClassName',
                'Some\ClassName',
                null,
            ],
            [
                self::defaultNaming(),
                'someclassname_classname',
                '\SomeClassName',
                'ClassName',
                null,
            ],
            [
                self::defaultNaming(),
                'name_classname',
                '\Some\Class\Name',
                'ClassName',
                null,
            ],

            // UnderscoreNamingStrategy
            [
                self::underscoreNamingLower(),
                'some_class_name_class_name',
                'SomeClassName',
                'Some\ClassName',
                null,
            ],
            [
                self::underscoreNamingLower(),
                'some_class_name_class_name',
                '\SomeClassName',
                'ClassName',
                null,
            ],
            [
                self::underscoreNamingLower(),
                'name_class_name',
                '\Some\Class\Name',
                'ClassName',
                null,
            ],

            [
                self::underscoreNamingUpper(),
                'SOME_CLASS_NAME_CLASS_NAME',
                'SomeClassName',
                'Some\ClassName',
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_CLASS_NAME_CLASS_NAME',
                '\SomeClassName',
                'ClassName',
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'NAME_CLASS_NAME',
                '\Some\Class\Name',
                'ClassName',
                null,
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $ownerEntity
     * @param string $associatedEntity
     * @param string $propertyName
     *
     * @dataProvider dataJoinTableName
     */
    public function testJoinTableName(NamingStrategy $strategy, $expected, $ownerEntity, $associatedEntity, $propertyName = null) : void
    {
        self::assertEquals($expected, $strategy->joinTableName($ownerEntity, $associatedEntity, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinKeyColumnName
     *
     * @return array
     */
    public static function dataJoinKeyColumnName()
    {
        return [
            // DefaultNamingStrategy
            [
                self::defaultNaming(),
                'someclassname_id',
                'SomeClassName',
                null,
                null,
            ],
            [
                self::defaultNaming(),
                'name_identifier',
                '\Some\Class\Name',
                'identifier',
                null,
            ],

            // UnderscoreNamingStrategy
            [
                self::underscoreNamingLower(),
                'some_class_name_id',
                'SomeClassName',
                null,
                null,
            ],
            [
                self::underscoreNamingLower(),
                'class_name_identifier',
                '\Some\Class\ClassName',
                'identifier',
                null,
            ],

            [
                self::underscoreNamingUpper(),
                'SOME_CLASS_NAME_ID',
                'SomeClassName',
                null,
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'CLASS_NAME_IDENTIFIER',
                '\Some\Class\ClassName',
                'IDENTIFIER',
                null,
            ],
        ];
    }

    /**
     * @param string $expected
     * @param string $propertyEntityName
     * @param string $referencedColumnName
     * @param string $propertyName
     *
     * @dataProvider dataJoinKeyColumnName
     */
    public function testJoinKeyColumnName(NamingStrategy $strategy, $expected, $propertyEntityName, $referencedColumnName = null, $propertyName = null) : void
    {
        self::assertEquals($expected, $strategy->joinKeyColumnName($propertyEntityName, $referencedColumnName, $propertyName));
    }
}
