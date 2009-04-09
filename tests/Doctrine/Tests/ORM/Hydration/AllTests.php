<?php

namespace Doctrine\Tests\ORM\Hydration;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Hydration_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Hydration');

        $suite->addTestSuite('Doctrine\Tests\ORM\Hydration\ObjectHydratorTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Hydration\ArrayHydratorTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Hydration\ScalarHydratorTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Hydration\SingleScalarHydratorTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Hydration\ResultSetMappingTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Hydration_AllTests::main') {
    AllTests::main();
}