<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function strtolower;

class DDC719Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC719Group::class);
    }

    public function testIsEmptySqlGeneration(): void
    {
        $q = $this->_em->createQuery('SELECT g, c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC719Group g LEFT JOIN g.children c  WHERE g.parents IS EMPTY');

        $referenceSQL = 'SELECT g0_.id AS id_0, g0_.name AS name_1, g0_.description AS description_2, g1_.id as id_3, g1_.name AS name_4, g1_.description AS description_5 FROM groups g0_ LEFT JOIN groups_groups g2_ ON g0_.id = g2_.parent_id LEFT JOIN groups g1_ ON g1_.id = g2_.child_id WHERE (SELECT COUNT(*) FROM groups_groups g3_ WHERE g3_.child_id = g0_.id) = 0';

        self::assertEquals(
            strtolower($referenceSQL),
            strtolower($q->getSQL()),
        );
    }
}

#[MappedSuperclass]
class MyEntity
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    protected $id;

    public function getId(): int
    {
        return $this->id;
    }
}

#[Table(name: 'groups')]
#[Entity]
class DDC719Group extends MyEntity
{
    /** @var string */
    #[Column(type: 'string', nullable: false)]
    protected $name;

    /** @var string */
    #[Column(type: 'string', nullable: true)]
    protected $description;

    /** @phpstan-var Collection<int, DDC719Group> */
    #[JoinTable(name: 'groups_groups')]
    #[JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'child_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'DDC719Group', inversedBy: 'parents')]
    protected $children = null;

    /** @phpstan-var Collection<int, DDC719Group> */
    #[ManyToMany(targetEntity: 'DDC719Group', mappedBy: 'children')]
    protected $parents = null;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->parents  = new ArrayCollection();
    }

    /**
     * adds group as new child
     */
    public function addGroup(Group $child): void
    {
        if (! $this->children->contains($child)) {
            $this->children->add($child);
            $child->addGroup($this);
        }
    }

    /**
     * adds channel as new child
     */
    public function addChannel(Channel $child): void
    {
        if (! $this->channels->contains($child)) {
            $this->channels->add($child);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /** @phpstan-return Collection<int, DDC719Group> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /** @phpstan-return Collection<int, DDC719Group> */
    public function getParents(): Collection
    {
        return $this->parents;
    }

    /** @phpstan-return Collection<int, Channel> */
    public function getChannels(): Collection
    {
        return $this->channels;
    }
}
