<?php

namespace Doctrine\Tests;

use Doctrine\Tests\Common;
use Doctrine\Tests\ORM;
use Doctrine\Tests\DBAL;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once dirname(__FILE__) . '/TestInit.php';

// Suites
#require_once 'Common/AllTests.php';
#require_once 'Dbal/AllTests.php';
#require_once 'Orm/AllTests.php';

class AllTests
{
    public static function main()
    {
        \PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new DoctrineTestSuite('Doctrine Tests');

        $suite->addTest(Common\AllTests::suite());
        $suite->addTest(DBAL\AllTests::suite());
        $suite->addTest(ORM\AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}