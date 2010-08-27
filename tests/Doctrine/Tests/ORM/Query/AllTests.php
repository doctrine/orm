<?php

namespace Doctrine\Tests\ORM\Query;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Query_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Orm Query');

        $suite->addTestSuite('Doctrine\Tests\ORM\Query\SelectSqlGenerationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\LanguageRecognitionTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\LexerTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\DeleteSqlGenerationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\UpdateSqlGenerationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\ExprTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\ParserResultTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Query\QueryTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Query_AllTests::main') {
    AllTests::main();
}