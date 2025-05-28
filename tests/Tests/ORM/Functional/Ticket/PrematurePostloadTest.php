<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class PrematurePostloadTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            User::class,
            SuperAdminUser::class,
            Person::class,
            Role::class,
            Manager::class
        );
    }

    public function testPrematurePostloadFiring(): void
    {
        $user       = new User();

        $superUser       = new SuperAdminUser($user);

        $person       = new Person();
        $person->user = $user;
        $user->person = $person;

        $role = new Manager($person);

        $this->_em->persist($user);
        $this->_em->persist($superUser);
        $this->_em->persist($person);
        $this->_em->persist($role);
        $this->_em->flush();
        $this->_em->clear();

        $people = $this->_em->createQueryBuilder()
            ->select('p, u, r')
            ->from(Person::class, 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.roles', 'r')
            ->getQuery()->getResult();

        foreach($people as $person) {
            self::assertNotNull($person->postloadedRoles);
        }
    }
}

class PersonListener {
    public function postLoad(Person $person): void
    {
        $person->postloadedRoles = $person->roles;
    }
}


#[ORM\Entity]
#[ORM\EntityListeners(["PersonListener"])]
class Person
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    public $postloadedRoles;


    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'person', fetch: 'EAGER', cascade: ['persist', 'remove'])]
    public ?User $user = null;

    #[ORM\OneToMany(targetEntity: Role::class, mappedBy: 'person', cascade: ['persist'])]
    public $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }
}

#[ORM\Entity]
class User
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    #[ORM\OneToOne(targetEntity: Person::class, mappedBy: 'user', fetch: 'EAGER')]
    public ?Person $person = null;

    #[ORM\OneToOne(targetEntity: SuperAdminUser::class, mappedBy: 'user')]
    private $superAdminUser;
}

#[ORM\Entity]
class SuperAdminUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'superAdminUser')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'position', type: 'string')]
#[ORM\DiscriminatorMap(['manager' => 'Manager'])]
abstract class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\OneToOne(targetEntity: Person::class, inversedBy: 'roles', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true)]
    protected $person;
}

#[ORM\Entity]
class Manager extends Role
{

    public function __construct(Person $person)
    {
        $this->person = $person;
    }
}
