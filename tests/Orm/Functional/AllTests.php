<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Functional_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Suites
require_once 'Orm/Functional/Ticket/AllTests.php';

// Tests
require_once 'Orm/Functional/BasicCRUDTest.php';

class Orm_Functional_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_OrmFunctionalTestSuite('Doctrine Orm Functional');

        $suite->addTestSuite('Orm_Functional_BasicCRUDTest');

        $suite->addTest(Orm_Functional_Ticket_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Functional_AllTests::main') {
    Orm_Functional_AllTests::main();
}