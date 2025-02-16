<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC211Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC211User::class, DDC211Group::class);
    }

    public function testIssue(): void
    {
        $user = new DDC211User();
        $user->setName('John Doe');

        $this->_em->persist($user);
        $this->_em->flush();

        $groupNames = ['group 1', 'group 2', 'group 3', 'group 4'];
        foreach ($groupNames as $name) {
            $group = new DDC211Group();
            $group->setName($name);
            $this->_em->persist($group);
            $this->_em->flush();

            if (! $user->getGroups()->contains($group)) {
                $user->getGroups()->add($group);
                $group->getUsers()->add($user);
                $this->_em->flush();
            }
        }

        self::assertEquals(4, $user->getGroups()->count());
    }
}


#[Table(name: 'ddc211_users')]
#[Entity]
class DDC211User
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /** @var string */
    #[Column(name: 'name', type: 'string', length: 255)]
    protected $name;

    /** @phpstan-var Collection<int, DDC211Group> */
    #[JoinTable(name: 'user_groups')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'DDC211Group', inversedBy: 'users')]
    protected $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @phpstan-return Collection<int, DDC211Group> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }
}

#[Table(name: 'ddc211_groups')]
#[Entity]
class DDC211Group
{
    /** @var int */
    #[Id]
    #[Column(name: 'id', type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /** @var string */
    #[Column(name: 'name', type: 'string', length: 255)]
    protected $name;

    /** @phpstan-var Collection<int, DDC211User> */
    #[ManyToMany(targetEntity: 'DDC211User', mappedBy: 'groups')]
    protected $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @phpstan-return Collection<int, DDC211User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}
