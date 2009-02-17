<?php

namespace Doctrine\Tests\Common;

use Doctrine\Tests\Common\Collections;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_AllTests::main');
}

require_once __DIR__ . '/../TestInit.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Common Tests');

        $suite->addTestSuite('Doctrine\Tests\Common\EventManagerTest');
        $suite->addTestSuite('Doctrine\Tests\Common\ClassLoaderTest');

        $suite->addTest(Collections\AllTests::suite());
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_AllTests::main') {
    AllTests::main();
}