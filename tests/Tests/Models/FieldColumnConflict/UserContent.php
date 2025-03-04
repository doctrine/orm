<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\FieldColumnConflict;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'user_content')]
#[Entity]
class UserContent
{
    #[Id]
    #[Column(type: 'string')]
    public string $id;

    #[Column(type: 'string')]
    public string $data;
}
