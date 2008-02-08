<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_Component_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Dbal/Component/TestTest.php';

class Dbal_Component_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Dbal Component');

        $suite->addTestSuite('Dbal_Component_TestTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_Component_AllTests::main') {
    Dbal_Component_AllTests::main();
}