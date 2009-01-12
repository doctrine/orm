<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Suites
require_once 'Common/Collections/AllTests.php';

class Common_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Common Tests');

        $suite->addTest(Common_Collections_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_AllTests::main') {
    Common_AllTests::main();
}