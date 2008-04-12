<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Suites
require_once 'Orm/Component/AllTests.php';
require_once 'Orm/Hydration/AllTests.php';
require_once 'Orm/Ticket/AllTests.php';

// Tests
require_once 'Orm/UnitOfWorkTestCase.php';

class Orm_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_OrmTestSuite('Doctrine Orm');

        $suite->addTestSuite('Orm_UnitOfWorkTestCase');
        //$suite->addTestSuite('Orm_ConfigurableTestCase');
        
        $suite->addTest(Orm_Component_AllTests::suite());
        $suite->addTest(Orm_Hydration_AllTests::suite());
        $suite->addTest(Orm_Ticket_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_AllTests::main') {
    Orm_AllTests::main();
}