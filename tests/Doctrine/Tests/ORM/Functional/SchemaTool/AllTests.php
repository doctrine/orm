<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Functional_Tools_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Functional Tools');

        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\SchemaTool\MySqlSchemaToolTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\SchemaTool\PostgreSqlSchemaToolTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\SchemaTool\DDC214Test');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Functional_Tools_AllTests::main') {
    AllTests::main();
}