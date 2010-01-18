<?php

namespace Doctrine\Tests\Common\Cli;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_Cli_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Common CLI Tests');

        $suite->addTestSuite('Doctrine\Tests\Common\Cli\ConfigurationTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cli\OptionTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cli\OptionGroupTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cli\StyleTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_Cli_AllTests::main') {
    AllTests::main();
}