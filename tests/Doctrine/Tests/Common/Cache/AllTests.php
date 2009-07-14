<?php

namespace Doctrine\Tests\Common\Cache;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Common_Cache_AllTests::main');
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
        $suite = new \Doctrine\Tests\DoctrineTestSuite('Doctrine Common Cache Tests');

        $suite->addTestSuite('Doctrine\Tests\Common\Cache\ApcCacheTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cache\ArrayCacheTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cache\MemcacheCacheTest');
        $suite->addTestSuite('Doctrine\Tests\Common\Cache\XcacheCacheTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Common_Cache_AllTests::main') {
    AllTests::main();
}