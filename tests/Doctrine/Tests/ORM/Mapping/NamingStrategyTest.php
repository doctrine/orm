<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Mapping\Factory\UnderscoreNamingStrategy;
use Doctrine\Tests\ORM\Mapping\NamingStrategy\JoinColumnClassNamingStrategy;
use Doctrine\Tests\ORM\Mapping\NamingStrategy\TablePrefixNamingStrategy;
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
     * @return TablePrefixNamingStrategy
     */
    private static function tablePrefixNaming()
    {
        return new TablePrefixNamingStrategy();
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

            // TablePrefixNamingStrategy
            [
                self::tablePrefixNaming(),
                'SomeClassName',
                'SomeClassName',
            ],
            [
                self::tablePrefixNaming(),
                'SomeClassName',
                '\SomeClassName',
            ],
            [
                self::tablePrefixNaming(),
                'Name',
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
                null,
            ],
            [
                self::defaultNaming(),
                'SOME_PROPERTY',
                'SOME_PROPERTY',
                null,
            ],
            [
                self::defaultNaming(),
                'some_property',
                'some_property',
                null,
            ],

            // UnderscoreNamingStrategy
            [
                self::underscoreNamingLower(),
                'some_property',
                'someProperty',
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'someProperty',
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'some_property',
                null,
            ],
            [
                self::underscoreNamingUpper(),
                'SOME_PROPERTY',
                'SOME_PROPERTY',
                null,
            ],

            // TablePrefixNamingStrategy
            [
                self::tablePrefixNaming(),
                'Entity_someProperty',
                'someProperty',
                'Some\Entity',
            ],
            [
                self::tablePrefixNaming(),
                'ENTITY_SOME_PROPERTY',
                'SOME_PROPERTY',
                'SOME\ENTITY',
            ],
            [
                self::tablePrefixNaming(),
                'entity_some_property',
                'some_property',
                'some\entity',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param string      $propertyName
     * @param string|null $className
     *
     * @dataProvider dataPropertyToColumnName
     */
    public function testPropertyToColumnName(NamingStrategy $strategy, $expected, $propertyName, $className) : void
    {
        self::assertEquals($expected, $strategy->propertyToColumnName($propertyName, $className));
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
            [self::defaultNaming(), 'id', null],

            // UnderscoreNamingStrategy
            [self::underscoreNamingLower(), 'id', null],
            [self::underscoreNamingUpper(), 'ID', null],

            // TablePrefixNamingStrategy
            [self::tablePrefixNaming(), 'Entity_id', 'Some\Entity'],
        ];
    }

    /**
     * @param string      $expected
     * @param string|null $targetEntity
     *
     * @dataProvider dataReferenceColumnName
     */
    public function testReferenceColumnName(NamingStrategy $strategy, $expected, $targetEntity) : void
    {
        self::assertEquals($expected, $strategy->referenceColumnName($targetEntity));
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

            // TablePrefixNamingStrategy
            [self::tablePrefixNaming(), 'ClassName_someColumn_id', 'someColumn', 'Some\ClassName'],
            [self::tablePrefixNaming(), 'ClassName_some_column_id', 'some_column', 'ClassName'],
        ];
    }

    /**
     * @param string      $expected
     * @param string      $propertyName
     * @param string|null $className
     *
     * @dataProvider dataJoinColumnName
     */
    public function testJoinColumnName(NamingStrategy $strategy, $expected, $propertyName, $className) : void
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

            // TablePrefixNamingStrategy
            [
                self::tablePrefixNaming(),
                'someclassname_classname',
                'SomeClassName',
                'Some\ClassName',
                null,
            ],
            [
                self::tablePrefixNaming(),
                'someclassname_classname',
                '\SomeClassName',
                'ClassName',
                null,
            ],
            [
                self::tablePrefixNaming(),
                'name_classname',
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

            // TablePrefixNamingStrategy
            [
                self::tablePrefixNaming(),
                'someclass_someanotherclass_someclassname_id',
                'SomeClassName',
                null,
                'someclass_someanotherclass',
            ],
            [
                self::tablePrefixNaming(),
                'someclass_someanotherclass_name_identifier',
                '\Some\Class\Name',
                'identifier',
                'someclass_someanotherclass',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param string      $propertyEntityName
     * @param string      $referencedColumnName
     * @param string|null $joinTableName
     *
     * @dataProvider dataJoinKeyColumnName
     */
    public function testJoinKeyColumnName(NamingStrategy $strategy, $expected, $propertyEntityName, $referencedColumnName = null, $joinTableName = null) : void
    {
        self::assertEquals($expected, $strategy->joinKeyColumnName($propertyEntityName, $referencedColumnName, $joinTableName));
    }
}
