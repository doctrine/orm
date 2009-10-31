<?php

namespace Doctrine\Tests\ORM\Tools\SchemaTool;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Tools_SchemaTool_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Schema Tool');

        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\SchemaTool\MysqlUpdateSchemaTest');


        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Tools_SchemaTool_AllTests::main') {
    AllTests::main();
}