<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_Component_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Orm/Component/TestTest.php';
require_once 'Orm/Component/AccessTest.php';
require_once 'Orm/Component/CollectionTest.php';

class Orm_Component_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Component');

//        $suite->addTestSuite('Orm_Component_TestTest');
				$suite->addTestSuite('Orm_Component_AccessTest');
				$suite->addTestSuite('Orm_Component_CollectionTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_Component_AllTests::main') {
    Dbal_Component_AllTests::main();
}
