<?php

namespace Doctrine\Tests\DBAL;

use Doctrine\Tests\DBAL\Component;
use Doctrine\Tests\DBAL\Ticker;
use Doctrine\Tests\DBAL\Functional;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Dbal_Platforms_AllTests::main');
}

require_once __DIR__ . '/../TestInit.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\DbalTestSuite('Doctrine DBAL');

        $suite->addTestSuite('Doctrine\Tests\DBAL\Platforms\SqlitePlatformTest');
        $suite->addTestSuite('Doctrine\Tests\DBAL\Platforms\MySqlPlatformTest');
        $suite->addTestSuite('Doctrine\Tests\DBAL\Platforms\PostgreSqlPlatformTest');
        $suite->addTestSuite('Doctrine\Tests\DBAL\Platforms\MsSqlPlatformTest');

        $suite->addTestSuite('Doctrine\Tests\DBAL\Types\DateTimeTest');

        $suite->addTest(Functional\AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Dbal_Platforms_AllTests::main') {
    AllTests::main();
}