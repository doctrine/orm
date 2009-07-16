<?php

namespace Doctrine\Tests\ORM\Functional;

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Orm_Functional_AllTests::main');
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
        $suite = new \Doctrine\Tests\OrmFunctionalTestSuite('Doctrine Orm Functional');

        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\BasicFunctionalTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\NativeQueryTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\SingleTableInheritanceTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\ClassTableInheritanceTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\DetachedEntityTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\QueryCacheTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\QueryTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\OneToOneUnidirectionalAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\OneToOneBidirectionalAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\OneToManyBidirectionalAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\ManyToManyUnidirectionalAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\ManyToManyBidirectionalAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\OneToOneSelfReferentialAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\OneToManySelfReferentialAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\ManyToManySelfReferentialAssociationTest');
        $suite->addTestSuite('Doctrine\Tests\ORM\Functional\DynamicProxyTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'Orm_Functional_AllTests::main') {
    AllTests::main();
}
