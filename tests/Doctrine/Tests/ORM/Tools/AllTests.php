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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Tools');

        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\Export\YamlClassMetadataExporterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\Export\XmlClassMetadataExporterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\Export\PhpClassMetadataExporterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\Export\AnnotationClassMetadataExporterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\ConvertDoctrine1SchemaTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\SchemaToolTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Tools\EntityGeneratorTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Tools_AllTests::main') {
    AllTests::main();
}