<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_Collections_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Common/Collections/CollectionTest.php';

class Common_Collections_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Common Collections Tests');

        $suite->addTestSuite('Common_Collections_CollectionTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_Collections_AllTests::main') {
    Common_Collections_AllTests::main();
}