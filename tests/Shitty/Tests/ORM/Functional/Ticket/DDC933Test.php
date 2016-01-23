<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Common\Collections\ArrayCollection;
use Shitty\Tests\Models\CMS\CmsUser;
use Shitty\Tests\Models\CMS\CmsGroup;

class DDC933Test extends \Shitty\Tests\OrmFunctionalTestCase
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
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $manager = new \Shitty\Tests\Models\Company\CompanyManager();
        $manager->setName('beberlei');
        $manager->setSalary(1234);
        $manager->setTitle('Vice President of This Test');
        $manager->setDepartment("Foo");

        $this->_em->persist($manager);
        $this->_em->flush();

        $this->_em->beginTransaction();
        $this->_em->lock($manager, \Shitty\DBAL\LockMode::PESSIMISTIC_READ);
        $this->_em->rollback();
    }
}
