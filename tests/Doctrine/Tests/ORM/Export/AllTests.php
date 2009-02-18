<?php

namespace Doctrine\Tests\ORM\Export;



if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Export_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Export');

        $suite->addTestSuite('Doctrine\Tests\ORM\Export\ClassExporterTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Export_AllTests::main') {
    AllTests::main();
}