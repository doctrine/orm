<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Associations_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Orm/Associations/OneToOneMappingTest.php';

class Orm_Associations_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Associations');

		$suite->addTestSuite('Orm_Associations_OneToOneMappingTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Associations_AllTests::main') {
    Orm_Associations_AllTests::main();
}
