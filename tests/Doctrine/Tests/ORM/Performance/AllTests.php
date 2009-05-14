<?php

namespace Doctrine\Tests\ORM\Performance;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Performance_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Performance');

        $suite->addTestSuite('Doctrine\Tests\ORM\Performance\HydrationPerformanceTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Performance_AllTests::main') {
    AllTests::main();
}