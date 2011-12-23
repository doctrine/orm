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
            array(self::defaultNaming(), 'SomeClassName',
                '\SomeClassName'
            ),
            array(self::defaultNaming(), 'Name',
                '\Some\Class\Name'
            ),
        );
    }

    /**
     * @dataProvider dataClassToTableName
     */
    public function testClassToTableName(NamingStrategy $strategy, $expected, $className)
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
     * Data Provider for NamingStrategy#referenceColumnName
     *
     * @return array
     */
    static public function dataReferenceColumnName()
    {
        return array(
            array(self::defaultNaming(), 'id'),
        );
    }

    /**
     * @dataProvider dataReferenceColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $joinedColumn
     * @param string            $joinedTable
     */
    public function testReferenceColumnName(NamingStrategy $strategy, $expected)
    {
        $this->assertEquals($expected, $strategy->referenceColumnName());
    }

    /**
     * Data Provider for NamingStrategy#joinColumnName
     *
     * @return array
     */
    static public function dataJoinColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someColumn_id',
                'someColumn', null,
            ),
            array(self::defaultNaming(), 'somecolumn_id',
                'somecolumn', null,
            ),
            array(self::defaultNaming(), 'some_column_id',
                'some_column', null,
            ),
        );
    }

    /**
     * @dataProvider dataJoinColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $propertyName
     */
    public function testJoinColumnName(NamingStrategy $strategy, $expected, $propertyName)
    {
        $this->assertEquals($expected, $strategy->joinColumnName($propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinTableName
     *
     * @return array
     */
    static public function dataJoinTableName()
    {
        return array(
            array(self::defaultNaming(), 'someclassname_classname',
                'SomeClassName', 'Some\ClassName', null,
            ),
            array(self::defaultNaming(), 'someclassname_classname',
                '\SomeClassName', 'ClassName', null,
            ),
            array(self::defaultNaming(), 'name_classname',
                '\Some\Class\Name', 'ClassName', null,
            ),
        );
    }

    /**
     * @dataProvider dataJoinTableName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $ownerEntity
     * @param string            $associatedEntity
     * @param string            $propertyName
     */
    public function testJoinTableName(NamingStrategy $strategy, $expected, $ownerEntity, $associatedEntity, $propertyName = null)
    {
        $this->assertEquals($expected, $strategy->joinTableName($ownerEntity, $associatedEntity, $propertyName));
    }

    /**
     * Data Provider for NamingStrategy#joinKeyColumnName
     *
     * @return array
     */
    static public function dataJoinKeyColumnName()
    {
        return array(
            array(self::defaultNaming(), 'someclassname_id',
                'SomeClassName', null, null,
            ),
            array(self::defaultNaming(), 'name_identifier',
                '\Some\Class\Name', 'identifier', null,
            ),
        );
    }

    /**
     * @dataProvider dataJoinKeyColumnName
     *
     * @param NamingStrategy    $strategy
     * @param string            $expected
     * @param string            $propertyEntityName
     * @param string            $referencedColumnName
     * @param string            $propertyName
     */
    public function testJoinKeyColumnName(NamingStrategy $strategy, $expected, $propertyEntityName, $referencedColumnName = null, $propertyName = null)
    {
        $this->assertEquals($expected, $strategy->joinKeyColumnName($propertyEntityName, $referencedColumnName, $propertyName));
    }
}