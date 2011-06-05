<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use DateTime, Doctrine\DBAL\Types\Type;

class DDC1193Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1193Company'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1193Person'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1193Account')
        ));
    }

    /**
     * @group DDC-1193
     */
    public function testIssue()
    {
        $company = new DDC1193Company();
        $person = new DDC1193Person();
        $account = new DDC1193Account();

        $person->account = $account;
        $person->company = $company;

        $company->member = $person;

        $this->_em->persist($company);

        $this->_em->flush();

        $companyId = $company->id;
        $accountId = $account->id;
        $this->_em->clear();

        $company = $this->_em->find(get_class($company), $companyId);

        $this->assertTrue($this->_em->getUnitOfWork()->isInIdentityMap($company), "Company is in identity map.");
        $this->assertFalse($company->member->__isInitialized__, "Pre-Condition");
        $this->assertTrue($this->_em->getUnitOfWork()->isInIdentityMap($company->member), "Member is in identity map.");
        
        $this->_em->remove($company);
        $this->_em->flush();

        $this->assertEquals(count($this->_em->getRepository(get_class($account))->findAll()), 0);
    }
}

/** @Entity */
class DDC1193Company {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /** @OneToOne(targetEntity="DDC1193Person", cascade={"persist", "remove"}) */
    public $member;

}

/** @Entity */
class DDC1193Person {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1193Account", cascade={"persist", "remove"})
     */
    public $account;
}

/** @Entity */
class DDC1193Account {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

}


