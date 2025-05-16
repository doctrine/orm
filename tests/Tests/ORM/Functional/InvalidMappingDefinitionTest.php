<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 */
class InvalidMappingDefinitionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testManyToManyRelationWithJoinTableOnTheWrongSide(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Mapping error on field 'owners' in Doctrine\Tests\ORM\Functional\OwnedSideEntity : 'joinTable' can only be set on many-to-many owning side.");

        $this->createSchemaForModels(
            OwningSideEntity::class,
            OwnedSideEntity::class,
        );
    }
}

#[Table(name: 'owning_side_entities1')]
#[Entity]
class OwningSideEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: OwnedSideEntity::class, inversedBy: 'owners')]
    #[ORM\JoinTable(name: 'owning_owned')]
    private Collection $relations;

    public function __construct()
    {
        $this->relations = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRelations(): Collection
    {
        return $this->relations;
    }

    public function addRelation(OwnedSideEntity $ownedSide): void
    {
        if (! $this->relations->contains($ownedSide)) {
            $this->relations->add($ownedSide);
            $ownedSide->addOwner($this);
        }
    }

    public function removeRelation(OwnedSideEntity $ownedSide): void
    {
        if ($this->relations->removeElement($ownedSide)) {
            $ownedSide->removeOwner($this);
        }
    }
}

#[Table(name: 'owned_side_entities1')]
#[Entity]
class OwnedSideEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $data;

    #[ORM\ManyToMany(targetEntity: OwningSideEntity::class, mappedBy: 'relations')]
    #[ORM\JoinTable(name: 'owning_owned')]
    private Collection $owners;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getOwners(): Collection
    {
        return $this->owners;
    }

    public function addOwner(OwningSideEntity $owningSide): void
    {
        if (! $this->owners->contains($owningSide)) {
            $this->owners->add($owningSide);
        }
    }

    public function removeOwner(OwningSideEntity $owningSide): void
    {
        $this->owners->removeElement($owningSide);
    }
}
