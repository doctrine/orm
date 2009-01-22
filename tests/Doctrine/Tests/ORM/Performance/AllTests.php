<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Performance_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
//...

class Orm_Performance_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Performance');

		//$suite->addTestSuite('Orm_Performance_xxxTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Performance_AllTests::main') {
    Orm_Performance_AllTests::main();
}
