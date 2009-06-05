<?php

namespace Doctrine\Tests\ORM\Mapping;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Mapping_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Mapping');

        $suite->addTestSuite('Doctrine\Tests\ORM\Mapping\ClassMetadataTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Mapping\XmlDriverTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Mapping\YamlDriverTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Mapping\ClassMetadataFactoryTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Mapping_AllTests::main') {
    AllTests::main();
}