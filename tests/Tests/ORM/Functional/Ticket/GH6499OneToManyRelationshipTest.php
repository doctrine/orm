<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH6499')]
class GH6499OneToManyRelationshipTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(Application::class, Person::class, ApplicationPerson::class);
    }

    /**
     * Test for the bug described in issue #6499.
     */
    public function testIssue(): void
    {
        $person = new Person();
        $this->_em->persist($person);

        $application = new Application();
        $this->_em->persist($application);

        $applicationPerson = new ApplicationPerson($person, $application);

        $this->_em->persist($applicationPerson);
        $this->_em->flush();
        $this->_em->clear();

        $personFromDatabase      = $this->_em->find(Person::class, $person->id);
        $applicationFromDatabase = $this->_em->find(Application::class, $application->id);

        self::assertEquals($personFromDatabase->id, $person->id, 'Issue #6499 will result in an integrity constraint violation before reaching this point.');
        self::assertFalse($personFromDatabase->getApplicationPeople()->isEmpty());

        self::assertEquals($applicationFromDatabase->id, $application->id, 'Issue #6499 will result in an integrity constraint violation before reaching this point.');
        self::assertFalse($applicationFromDatabase->getApplicationPeople()->isEmpty());
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'GH6499OTM_application')]
class Application
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var Collection */
    #[ORM\OneToMany(mappedBy: 'application', targetEntity: ApplicationPerson::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $applicationPeople;

    public function __construct()
    {
        $this->applicationPeople = new ArrayCollection();
    }

    public function getApplicationPeople(): Collection
    {
        return $this->applicationPeople;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'GH6499OTM_person')]
class Person
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var Collection */
    #[ORM\OneToMany(mappedBy: 'person', targetEntity: ApplicationPerson::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private $applicationPeople;

    public function __construct()
    {
        $this->applicationPeople = new ArrayCollection();
    }

    public function getApplicationPeople(): Collection
    {
        return $this->applicationPeople;
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'GH6499OTM_application_person')]
class ApplicationPerson
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var Application */
    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'applicationPeople', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    public $application;

    /** @var Person */
    #[ORM\ManyToOne(targetEntity: Person::class, inversedBy: 'applicationPeople', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    public $person;

    public function __construct(Person $person, Application $application)
    {
        $this->person      = $person;
        $this->application = $application;
    }
}
