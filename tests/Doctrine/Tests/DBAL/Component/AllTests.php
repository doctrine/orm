<?php

namespace Doctrine\Tests\DBAL\Component;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_Component_AllTests::main');
}

require_once dirname(__FILE__) . '/../../TestInit.php';

// Tests
#require_once 'Dbal/Component/TestTest.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Dbal Component');

        $suite->addTestSuite('Doctrine\Tests\DBAL\Component\TestTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_Component_AllTests::main') {
    AllTests::main();
}