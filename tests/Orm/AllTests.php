<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Suites
require_once 'Orm/Query/AllTests.php';
require_once 'Orm/Hydration/AllTests.php';
require_once 'Orm/Ticket/AllTests.php';
require_once 'Orm/Entity/AllTests.php';
require_once 'Orm/Associations/AllTests.php';
require_once 'Orm/Mapping/AllTests.php';
require_once 'Orm/Functional/AllTests.php';

// Tests
require_once 'Orm/UnitOfWorkTest.php';
require_once 'Orm/EntityManagerTest.php';
require_once 'Orm/EntityPersisterTest.php';
require_once 'Orm/CommitOrderCalculatorTest.php';

class Orm_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_OrmTestSuite('Doctrine Orm');

        $suite->addTestSuite('Orm_UnitOfWorkTest');
        $suite->addTestSuite('Orm_EntityManagerTest');
        $suite->addTestSuite('Orm_EntityPersisterTest');
        $suite->addTestSuite('Orm_CommitOrderCalculatorTest');
        
        $suite->addTest(Orm_Query_AllTests::suite());
        $suite->addTest(Orm_Hydration_AllTests::suite());
        $suite->addTest(Orm_Entity_AllTests::suite());
        $suite->addTest(Orm_Ticket_AllTests::suite());
        $suite->addTest(Orm_Associations_AllTests::suite());
        $suite->addTest(Orm_Mapping_AllTests::suite());
        $suite->addTest(Orm_Functional_AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_AllTests::main') {
    Orm_AllTests::main();
}
