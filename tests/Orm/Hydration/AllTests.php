<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Hydration_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
//require_once 'Orm/Hydration/BasicHydrationTest.php';
require_once 'Orm/Hydration/ObjectHydratorTest.php';
require_once 'Orm/Hydration/ArrayHydratorTest.php';
require_once 'Orm/Hydration/ScalarHydratorTest.php';
require_once 'Orm/Hydration/SingleScalarHydratorTest.php';

class Orm_Hydration_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Hydration');

        //$suite->addTestSuite('Orm_Hydration_BasicHydrationTest');
        $suite->addTestSuite('Orm_Hydration_ObjectHydratorTest');
        $suite->addTestSuite('Orm_Hydration_ArrayHydratorTest');
        $suite->addTestSuite('Orm_Hydration_ScalarHydratorTest');
        $suite->addTestSuite('Orm_Hydration_SingleScalarHydratorTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Hydration_AllTests::main') {
    Orm_Hydration_AllTests::main();
}
