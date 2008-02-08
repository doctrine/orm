<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Ticket_AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'lib/Doctrine_TestSuite.php';

require_once 'Ticket/1Test.php';

class Ticket_AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new Doctrine_TestSuite('Doctrine Orm');

        $suite->addTestSuite('Ticket_1Test');
        
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Ticket_AllTests::main') {
    Ticket_AllTests::main();
}