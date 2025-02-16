<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table('cache_traveler_profile')]
#[Entity]
#[Cache('NONSTRICT_READ_WRITE')]
class TravelerProfile
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    protected $id;

    #[OneToOne(targetEntity: 'TravelerProfileInfo', mappedBy: 'profile')]
    #[Cache]
    private TravelerProfileInfo|null $info = null;

    public function __construct(
        #[Column(unique: true)]
        private string $name,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $nae): void
    {
        $this->name = $nae;
    }

    public function getInfo(): TravelerProfileInfo
    {
        return $this->info;
    }

    public function setInfo(TravelerProfileInfo $info): void
    {
        $this->info = $info;
    }
}
