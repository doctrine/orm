<?php

namespace Doctrine\Tests\ORM\Id;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Id_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Id');

        $suite->addTestSuite('Doctrine\Tests\ORM\Id\SequenceGeneratorTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Id\AssignedIdTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Id_AllTests::main') {
    AllTests::main();
}