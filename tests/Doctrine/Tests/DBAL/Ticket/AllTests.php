<?php

namespace Doctrine\Tests\DBAL\Ticket;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Ticket_AllTests::main');
}

require_once __DIR__ . '/../../TestInit.php';

// Tests
#require_once 'Dbal/Ticket/1Test.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm');

        $suite->addTestSuite('Doctrine\Tests\DBAL\Ticket\Test1');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Ticket_AllTests::main') {
    AllTests::main();
}