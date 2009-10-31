<?php

namespace Doctrine\Tests\ORM\Tools;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Tools_AllTests::main');
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

        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\Export\ClassMetadataExporterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\ConvertDoctrine1SchemaTest');

        $suite->addTest(SchemaTool\AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Tools_AllTests::main') {
    AllTests::main();
}