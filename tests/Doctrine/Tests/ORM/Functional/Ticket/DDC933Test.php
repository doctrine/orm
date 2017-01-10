<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC933Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    /**
     * @group DDC-933
     */
    public function testLockCTIClass()
    {
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $manager = new \Doctrine\Tests\Models\Company\CompanyManager();
        $manager->setName('beberlei');
        $manager->setSalary(1234);
        $manager->setTitle('Vice President of This Test');
        $manager->setDepartment("Foo");

        $this->em->persist($manager);
        $this->em->flush();

        $this->em->beginTransaction();
        $this->em->lock($manager, \Doctrine\DBAL\LockMode::PESSIMISTIC_READ);
        $this->em->rollback();
    }
}
