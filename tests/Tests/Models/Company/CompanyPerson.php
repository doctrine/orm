<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Company;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Description of CompanyPerson
 */
#[Table(name: 'company_persons')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string', length: 255)]
#[DiscriminatorMap(['person' => 'CompanyPerson', 'manager' => 'CompanyManager', 'employee' => 'CompanyEmployee'])]
class CompanyPerson
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[Column]
    private string|null $name = null;

    #[OneToOne(targetEntity: 'CompanyPerson')]
    #[JoinColumn(name: 'spouse_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private CompanyPerson|null $spouse = null;

    /** @phpstan-var Collection<int, CompanyPerson> */
    #[JoinTable(name: 'company_persons_friends')]
    #[JoinColumn(name: 'person_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[InverseJoinColumn(name: 'friend_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ManyToMany(targetEntity: 'CompanyPerson')]
    private $friends;

    public function __construct()
    {
        $this->friends = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSpouse(): CompanyPerson|null
    {
        return $this->spouse;
    }

    /** @phpstan-return Collection<int, CompanyPerson> */
    public function getFriends(): Collection
    {
        return $this->friends;
    }

    public function addFriend(CompanyPerson $friend): void
    {
        if (! $this->friends->contains($friend)) {
            $this->friends->add($friend);
            $friend->addFriend($this);
        }
    }

    public function setSpouse(CompanyPerson $spouse): void
    {
        if ($spouse !== $this->spouse) {
            $this->spouse = $spouse;
            $this->spouse->setSpouse($this);
        }
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(
            ['name' => 'company_person'],
        );
    }
}
