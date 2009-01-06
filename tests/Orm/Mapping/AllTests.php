<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Mapping_AllTests::main');
}

require_once 'lib/DoctrineTestInit.php';

// Tests
require_once 'Orm/Mapping/ClassMetadataTest.php';
require_once 'Orm/Mapping/ClassMetadataFactoryTest.php';

class Orm_Mapping_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm Mapping');

		$suite->addTestSuite('Orm_Mapping_ClassMetadataTest');
        $suite->addTestSuite('Orm_Mapping_ClassMetadataFactoryTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Mapping_AllTests::main') {
    Orm_Mapping_AllTests::main();
}
