<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Functional_Ticket_AllTests::main');
}

require_once __DIR__ . '/../../../TestInit.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\OrmFunctionalTestSuite('Doctrine Orm Ticket Tests');

        $tests = glob(__DIR__ . '/Ticket*Test.php');
        foreach ($tests as $test) {
            $info = pathinfo($test);
            $suite->addTestSuite('Doctrine\Tests\ORM\Functional\Ticket\\' . $info['filename']);
        }

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Functional_Ticket_AllTests::main') {
    AllTests::main();
}
