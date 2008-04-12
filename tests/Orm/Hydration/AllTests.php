<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Hydration_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Orm/Hydration/BasicHydrationTest.php';

class Orm_Hydration_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Hydration');

        $suite->addTestSuite('Orm_Hydration_BasicHydrationTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Hydration_AllTests::main') {
    Orm_Hydration_AllTests::main();
}
