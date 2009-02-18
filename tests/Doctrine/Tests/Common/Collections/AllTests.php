<?php

namespace Doctrine\Tests\Common\Collections;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_Collections_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Common Collections Tests');

        $suite->addTestSuite('Doctrine\Tests\Common\Collections\CollectionTest');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_Collections_AllTests::main') {
    AllTests::main();
}