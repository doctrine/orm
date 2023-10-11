<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC2372;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\Models\DDC2372\Traits\DDC2372AddressAndAccessors;

#[Table(name: 'users')]
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn('type')]
#[DiscriminatorMap(['user' => 'DDC2372User', 'admin' => 'DDC2372Admin'])]
class DDC2372User
{
    use DDC2372AddressAndAccessors;

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[Column(type: 'string', length: 50)]
    private string|null $name = null;

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
}
