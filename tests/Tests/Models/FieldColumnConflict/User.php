<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\FieldColumnConflict;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: "user")]
#[Entity]
class User
{
    #[Id]
    #[Column(type: "integer", name: "user_id")]
    #[GeneratedValue]
    public int $id;

    #[OneToOne(targetEntity: UserContent::class)]
    #[JoinColumn(name: "id", referencedColumnName: "id")]
    public ?UserContent $userContent;
}