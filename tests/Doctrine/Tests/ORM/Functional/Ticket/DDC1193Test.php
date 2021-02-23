<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;
use function get_class;

class DDC1193Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1193Company::class),
                $this->em->getClassMetadata(DDC1193Person::class),
                $this->em->getClassMetadata(DDC1193Account::class),
            ]
        );
    }

    /**
     * @group DDC-1193
     */
    public function testIssue() : void
    {
        $company = new DDC1193Company();
        $person  = new DDC1193Person();
        $account = new DDC1193Account();

        $person->account = $account;
        $person->company = $company;

        $company->member = $person;

        $this->em->persist($company);

        $this->em->flush();

        $companyId = $company->id;

        $this->em->clear();

        /** @var DDC1193Company $company */
        $company = $this->em->find(DDC1193Company::class, $companyId);

        self::assertTrue($this->em->getUnitOfWork()->isInIdentityMap($company), 'Company is in identity map.');

        /** @var GhostObjectInterface|DDC1193Person $member */
        $member = $company->member;

        self::assertInstanceOf(GhostObjectInterface::class, $member);
        self::assertInstanceOf(DDC1193Person::class, $member);
        self::assertFalse($member->isProxyInitialized(), 'Pre-Condition');
        self::assertTrue($this->em->getUnitOfWork()->isInIdentityMap($company->member), 'Member is in identity map.');

        $this->em->remove($company);
        $this->em->flush();

        self::assertCount(0, $this->em->getRepository(get_class($account))->findAll());
    }
}

/** @ORM\Entity */
class DDC1193Company
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC1193Person::class, cascade={"persist", "remove"}) */
    public $member;
}

/** @ORM\Entity */
class DDC1193Person
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\OneToOne(targetEntity=DDC1193Account::class, cascade={"persist", "remove"}) */
    public $account;
}

/** @ORM\Entity */
class DDC1193Account
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}
