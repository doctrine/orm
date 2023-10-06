<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH10348Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10348Person::class,
            GH10348Company::class,
        ]);
    }

    public function testTheORMRemovesReferencedEmployeeBeforeReferencingEmployee(): void
    {
        $person1         = new GH10348Person();
        $person2         = new GH10348Person();
        $person2->mentor = $person1;

        $company = new GH10348Company();
        $company->addEmployee($person1)->addEmployee($person2);

        $this->_em->persist($company);
        $this->_em->flush();

        $company = $this->_em->find(GH10348Company::class, $company->id);

        $this->_em->remove($company);
        $this->_em->flush();

        self::assertEmpty($this->_em->createQuery('SELECT c FROM ' . GH10348Company::class . ' c')->getResult());
        self::assertEmpty($this->_em->createQuery('SELECT p FROM ' . GH10348Person::class . ' p')->getResult());
    }
}

#[ORM\Entity]
class GH10348Person
{
    /** @var ?int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id = null;

    /** @var ?GH10348Company */
    #[ORM\ManyToOne(targetEntity: GH10348Company::class, inversedBy: 'employees')]
    public $employer = null;

    /** @var ?GH10348Person */
    #[ORM\ManyToOne(targetEntity: self::class, cascade: ['remove'])]
    public $mentor = null;
}

#[ORM\Entity]
class GH10348Company
{
    /** @var ?int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id = null;

    /** @var Collection */
    #[ORM\OneToMany(mappedBy: 'employer', targetEntity: GH10348Person::class, cascade: ['persist', 'remove'])]
    private $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function addEmployee(GH10348Person $person): self
    {
        $person->employer = $this;
        $this->employees->add($person);

        return $this;
    }
}
