<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC1193Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC1193Company::class),
            $this->em->getClassMetadata(DDC1193Person::class),
            $this->em->getClassMetadata(DDC1193Account::class)
            ]
        );
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

        $this->em->persist($company);

        $this->em->flush();

        $companyId = $company->id;
        $accountId = $account->id;
        $this->em->clear();

        $company = $this->em->find(get_class($company), $companyId);

        self::assertTrue($this->em->getUnitOfWork()->isInIdentityMap($company), "Company is in identity map.");
        self::assertFalse($company->member->__isInitialized(), "Pre-Condition");
        self::assertTrue($this->em->getUnitOfWork()->isInIdentityMap($company->member), "Member is in identity map.");

        $this->em->remove($company);
        $this->em->flush();

        self::assertEquals(count($this->em->getRepository(get_class($account))->findAll()), 0);
    }
}

/** @ORM\Entity */
class DDC1193Company {
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\OneToOne(targetEntity="DDC1193Person", cascade={"persist", "remove"}) */
    public $member;

}

/** @ORM\Entity */
class DDC1193Person {
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC1193Account", cascade={"persist", "remove"})
     */
    public $account;
}

/** @ORM\Entity */
class DDC1193Account {
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

}
