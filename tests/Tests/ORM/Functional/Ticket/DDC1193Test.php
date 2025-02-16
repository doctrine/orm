<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC1193Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC1193Company::class,
            DDC1193Person::class,
            DDC1193Account::class,
        );
    }

    #[Group('DDC-1193')]
    public function testIssue(): void
    {
        $company = new DDC1193Company();
        $person  = new DDC1193Person();
        $account = new DDC1193Account();

        $person->account = $account;
        $company->member = $person;

        $this->_em->persist($company);
        $this->_em->flush();

        $companyId = $company->id;
        $this->_em->clear();

        $company = $this->_em->find($company::class, $companyId);

        self::assertTrue($this->_em->getUnitOfWork()->isInIdentityMap($company), 'Company is in identity map.');
        self::assertTrue($this->isUninitializedObject($company->member), 'Pre-Condition');
        self::assertTrue($this->_em->getUnitOfWork()->isInIdentityMap($company->member), 'Member is in identity map.');

        $this->_em->remove($company);
        $this->_em->flush();

        self::assertCount(0, $this->_em->getRepository($account::class)->findAll());
    }
}

#[Entity]
class DDC1193Company
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC1193Person */
    #[OneToOne(targetEntity: 'DDC1193Person', cascade: ['persist', 'remove'])]
    public $member;
}

#[Entity]
class DDC1193Person
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var DDC1193Account */
    #[OneToOne(targetEntity: 'DDC1193Account', cascade: ['persist', 'remove'])]
    public $account;
}

#[Entity]
class DDC1193Account
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}
