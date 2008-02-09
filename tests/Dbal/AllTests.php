<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Suites
require_once 'Dbal/Component/AllTests.php';

class Dbal_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_DbalTestSuite('Doctrine Dbal');

        $suite->addTest(Dbal_Component_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_AllTests::main') {
    Dbal_AllTests::main();
}