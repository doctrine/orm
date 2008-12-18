<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Entity_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Orm/Entity/AccessorTest.php';
require_once 'Orm/Entity/ConstructorTest.php';

class Orm_Entity_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Entity Tests');

        //$suite->addTestSuite('Orm_Entity_AccessorTest');
        $suite->addTestSuite('Orm_Entity_ConstructorTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Entity_AllTests::main') {
    Orm_Entity_AllTests::main();
}