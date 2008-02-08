<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_Component_AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'lib/Doctrine_TestSuite.php';

require_once 'Orm/Component/TestTest.php';

class Orm_Component_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Dbal Component');

        $suite->addTestSuite('Orm_Component_TestTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_Component_AllTests::main') {
    Dbal_Component_AllTests::main();
}