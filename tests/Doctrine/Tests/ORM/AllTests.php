<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Tests\ORM\Associations;
use Doctrine\Tests\ORM\Cache;
use Doctrine\Tests\ORM\Entity;
use Doctrine\Tests\ORM\Hydration;
use Doctrine\Tests\ORM\Mapping;
use Doctrine\Tests\ORM\Query;
use Doctrine\Tests\ORM\Ticket;
use Doctrine\Tests\ORM\Functional;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_AllTests::main');
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
        $suite = new \Doctrine\Tests\OrmTestSuite('Doctrine Orm');

        $suite->addTestSuite('Doctrine\Tests\ORM\UnitOfWorkTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\EntityManagerTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\EntityPersisterTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\CommitOrderCalculatorTest');
        
        $suite->addTest(Query\AllTests::suite());
        $suite->addTest(Hydration\AllTests::suite());
        $suite->addTest(Entity\AllTests::suite());
        $suite->addTest(Associations\AllTests::suite());
        $suite->addTest(Mapping\AllTests::suite());
        $suite->addTest(Functional\AllTests::suite());

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_AllTests::main') {
    AllTests::main();
}
