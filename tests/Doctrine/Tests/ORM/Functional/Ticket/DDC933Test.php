<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\LockMode;
use Doctrine\Tests\Models\Company\CompanyManager;
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
        $manager = new CompanyManager();
        $manager->setName('beberlei');
        $manager->setSalary(1234);
        $manager->setTitle('Vice President of This Test');
        $manager->setDepartment("Foo");

        $this->_em->persist($manager);
        $this->_em->flush();

        $this->_em->beginTransaction();
        $this->_em->lock($manager, LockMode::PESSIMISTIC_READ);
        $this->_em->rollback();
    }
}
