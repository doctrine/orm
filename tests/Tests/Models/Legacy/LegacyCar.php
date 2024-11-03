<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Legacy;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'legacy_cars')]
#[Entity]
class LegacyCar
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(name: 'iCarId', type: 'integer', nullable: false)]
    public $id;

    /** @phpstan-var Collection<int, LegacyUser> */
    #[ManyToMany(targetEntity: 'LegacyUser', mappedBy: 'cars')]
    public $users;

    /** @var string */
    #[Column(name: 'sDescription', type: 'string', length: 255, unique: true)]
    public $description;

    public function getDescription(): string
    {
        return $this->description;
    }

    public function addUser(LegacyUser $user): void
    {
        $this->users[] = $user;
    }

    /** @phpstan-return Collection<int, LegacyUser> */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}
