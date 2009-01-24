<?php

namespace Doctrine\Tests\ORM\Functional;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Functional_AllTests::main');
}

require_once __DIR__ . '/../../TestInit.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\OrmFunctionalTestSuite('Doctrine Orm Functional');

        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\BasicCRUDTest');

        //$suite->addTest(Orm_Functional_Ticket_AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Functional_AllTests::main') {
    AllTests::main();
}