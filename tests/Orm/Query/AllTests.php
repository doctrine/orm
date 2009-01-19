<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Query_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

require_once 'IdentifierRecognitionTest.php';
/*require_once 'ScannerTest.php';
require_once 'DqlGenerationTest.php';
require_once 'DeleteSqlGenerationTest.php';
require_once 'UpdateSqlGenerationTest.php';
require_once 'SelectSqlGenerationTest.php';
require_once 'LanguageRecognitionTest.php';*/

class Orm_Query_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Query');

        $suite->addTestSuite('Orm_Query_IdentifierRecognitionTest');
        $suite->addTestSuite('Orm_Query_SelectSqlGenerationTest');
        /*
        $suite->addTestSuite('Orm_Query_LanguageRecognitionTest');
        $suite->addTestSuite('Orm_Query_ScannerTest');
        $suite->addTestSuite('Orm_Query_DqlGenerationTest');
        $suite->addTestSuite('Orm_Query_DeleteSqlGenerationTest');
        $suite->addTestSuite('Orm_Query_UpdateSqlGenerationTest');*/

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Query_AllTests::main') {
    Orm_Query_AllTests::main();
}
