<?php

namespace Doctrine\Tests\DBAL;

use Doctrine\Tests\DBAL\Component;
use Doctrine\Tests\DBAL\Ticker;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_AllTests::main');
}

require_once __DIR__ . '/../TestInit.php';

// Suites
#require_once 'Dbal/Component/AllTests.php';
#require_once 'Dbal/Ticket/AllTests.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\DbalTestSuite('Doctrine DBAL');

        $suite->addTestSuite('Doctrine\Tests\DBAL\Platforms\AbstractPlatformTest');

        $suite->addTest(Ticket\AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_AllTests::main') {
    AllTests::main();
}