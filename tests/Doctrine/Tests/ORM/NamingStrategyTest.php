<?php

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\DefaultNamingStrategy;
use Doctrine\ORM\NamingStrategy;

require_once __DIR__ . '/../TestInit.php';

/**
 * @group DDC-559
 */
class NamingStrategyTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var DefaultNamingStrategy
     */
    private static $defaultNamingStrategy;
    
    /**
     * @return DefaultNamingStrategy
     */
    static private function defaultNaming()
    {
        if (self::$defaultNamingStrategy == null) {
           self::$defaultNamingStrategy = new DefaultNamingStrategy();
        }
        return self::$defaultNamingStrategy;
    }

    /**
     * Data Provider for NamingStrategy#classToTableName
     *
     * @return array
     */
    static public function dataClassToTableName()
    {
        return array(
            array(self::defaultNaming(), 'SomeClassName',
                'SomeClassName'
            ),
            array(self::defaultNaming(), 'SOME_CLASS_NAME',
                'SOME_CLASS_NAME'
            ),
            array(self::defaultNaming(), 'some_class_name',
                'some_class_name'
            ),
        );
    }

    /**
     * @dataProvider dataClassToTableName
     */
    public function testClassToTableName(NamingStrategy $strategy, $className, $expected)
    {
        $this->assertEquals($expected, $strategy->classToTableName($className));
    }

    /**
     * Data Provider for NamingStrategy#propertyToColumnName
     * 
     * @return array
     */
    static public function dataPropertyToColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someProperty',
                'someProperty'
            ),
            array(self::defaultNaming(), 'SOME_PROPERTY',
                'SOME_PROPERTY'
            ),
            array(self::defaultNaming(), 'some_property',
                'some_property'
            ),
        );
    }
    
    /**
     * @dataProvider dataPropertyToColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $propertyName
     */
    public function testPropertyToColumnName(NamingStrategy $strategy, $expected, $propertyName)
    {
        $this->assertEquals($expected, $strategy->propertyToColumnName($propertyName));
    }

    /**
     * Data Provider for NamingStrategy#tableName
     *
     * @return array
     */
    static public function dataTableName()
    {
        return array(
            array(self::defaultNaming(), 'someTable',
                'someTable'
            ),
            array(self::defaultNaming(), 'SOME_TABLE',
                'SOME_TABLE'
            ),
            array(self::defaultNaming(), 'some_table',
                'some_table'
            ),
        );
    }
    
    /**
     * @dataProvider dataTableName
     * 
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $tableName
     */
    public function testTableName(NamingStrategy $strategy, $expected, $tableName)
    {
        $this->assertEquals($expected, $strategy->tableName($tableName));
    }

    /**
     * Data Provider for NamingStrategy#columnName
     *
     * @return array
     */
    static public function dataColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                'someColumn'
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN'
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column'
            ),
        );
    }
    
    /**
     * @dataProvider dataColumnName
     * 
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $columnName
     */
    public function testColumnName(NamingStrategy $strategy, $expected, $columnName)
    {
        $this->assertEquals($expected, $strategy->columnName($columnName));
    }

    /**
     * Data Provider for NamingStrategy#collectionTableName
     *
     * @return array
     */
    static public function dataCollectionTableName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                null, null, null, null, 'someColumn',
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                null, null, null, null, 'SOME_COLUMN'
            ),
            array(self::defaultNaming(), 'some_column',
                null, null, null, null, 'some_column'
            ),
        );
    }

    /**
     * @dataProvider dataCollectionTableName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $ownerEntity
     * @param string            $ownerEntityTable
     * @param string            $associatedEntity
     * @param string            $associatedEntityTable
     * @param string            $propertyName
     */
    public function testCollectionTableName(NamingStrategy $strategy, $expected, $ownerEntity, $ownerEntityTable, $associatedEntity, $associatedEntityTable, $propertyName)
    {
        $this->assertEquals($expected, $strategy->collectionTableName($ownerEntity, $ownerEntityTable, $associatedEntity, $associatedEntityTable, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinKeyColumnName
     *
     * @return array
     */
    static public function dataJoinKeyColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                'someColumn', null,
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN', null,
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column', null,
            ),
        );
    }

    /**
     * @dataProvider dataJoinKeyColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $joinedColumn
     * @param string            $joinedTable
     */
    public function testJoinKeyColumnName(NamingStrategy $strategy, $expected, $joinedColumn, $joinedTable)
    {
        $this->assertEquals($expected, $strategy->joinKeyColumnName($joinedColumn, $joinedTable));
    }

    /**
     * Data Provider for NamingStrategy#foreignKeyColumnName
     *
     * @return array
     */
    static public function dataForeignKeyColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                'someColumn', null, null, null,
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN', null, null, null,
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column', null, null, null,
            ),

            array(self::defaultNaming(), 'someColumn',
                null, null, 'someColumn', null,
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                null, null, 'SOME_COLUMN', null,
            ),
            array(self::defaultNaming(), 'some_column',
                null, null, 'some_column', null,
            ),
        );
    }

    /**
     * @dataProvider dataForeignKeyColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $propertyName
     * @param string            $propertyEntityName
     * @param string            $propertyTableName
     * @param string            $referencedColumnName
     */
    public function testForeignKeyColumnName(NamingStrategy $strategy, $expected, $propertyName, $propertyEntityName, $propertyTableName, $referencedColumnName)
    {
        $this->assertEquals($expected, $strategy->foreignKeyColumnName($propertyName, $propertyEntityName, $propertyTableName, $referencedColumnName));
    }

    /**
     * Data Provider for NamingStrategy#logicalColumnName
     *
     * @return array
     */
    static public function dataLogicalColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                'someColumn', null,
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN', null,
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column', null,
            ),

            array(self::defaultNaming(), 'someColumn',
                null, 'someColumn',
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                null, 'SOME_COLUMN',
            ),
            array(self::defaultNaming(), 'some_column',
                null, 'some_column',
            ),
        );
    }

    /**
     * @dataProvider dataLogicalColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $columnName
     * @param string            $propertyName
     */
    public function testLogicalColumnName(NamingStrategy $strategy, $expected, $columnName, $propertyName)
    {
        $this->assertEquals($expected, $strategy->logicalColumnName($columnName, $propertyName));
    }


    /**
     * Data Provider for NamingStrategy#logicalCollectionTableName
     *
     * @return array
     */
    static public function dataLogicalCollectionTableName()
    {
        return array(
            array(self::defaultNaming(), 'SomeClassName_SomeAssocClassName',
                null, 'SomeClassName', 'SomeAssocClassName', null
            ),
            array(self::defaultNaming(), 'SOME_CLASS_NAME_SOME_ASSOC_CLASS_NAME',
                null, 'SOME_CLASS_NAME', 'SOME_ASSOC_CLASS_NAME', null
            ),
            array(self::defaultNaming(), 'some_class_name_some_assoc_class_name',
                null, 'some_class_name', 'some_assoc_class_name', null
            ),

            array(self::defaultNaming(), 'SomeClassName_someProperty',
                null, 'SomeClassName', null, 'someProperty',
            ),
            array(self::defaultNaming(), 'SOME_CLASS_NAME_SOME_PROPERTY',
                null, 'SOME_CLASS_NAME', null, 'SOME_PROPERTY',
            ),
            array(self::defaultNaming(), 'some_class_name_some_property',
                null, 'some_class_name', null, 'some_property',
            ),

        );
    }

    /**
     * @dataProvider dataLogicalCollectionTableName
     *
     * @param NamingStrategy    $strategy
     * @param string            $tableName
     * @param string            $ownerEntityTable
     * @param string            $associatedEntityTable
     * @param string            $propertyName
     */
    public function testLogicalCollectionTableName(NamingStrategy $strategy, $expected, $tableName, $ownerEntityTable, $associatedEntityTable, $propertyName)
    {
        $this->assertEquals($expected, $strategy->logicalCollectionTableName($tableName, $ownerEntityTable, $associatedEntityTable, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#logicalCollectionColumnName
     *
     * @return array
     */
    static public function dataLogicalCollectionColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn',
                'someColumn', null, null,
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN', null, null,
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column', null, null,
            ),

            array(self::defaultNaming(), 'someColumn',
                'someColumn', 'some', 'Column',
            ),
            array(self::defaultNaming(), 'SOME_COLUMN',
                'SOME_COLUMN', 'SOME', 'COLUMN',
            ),
            array(self::defaultNaming(), 'some_column',
                'some_column', 'some', 'column',
            ),

        );
    }

    /**
     * @dataProvider dataLogicalCollectionColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $columnName
     * @param string            $propertyName
     * @param string            $referencedColumn
     */
    public function testLogicalCollectionColumnName(NamingStrategy $strategy, $expected, $columnName, $propertyName, $referencedColumn)
    {
        $this->assertEquals($expected, $strategy->logicalCollectionColumnName($columnName, $propertyName, $referencedColumn));
    }

}