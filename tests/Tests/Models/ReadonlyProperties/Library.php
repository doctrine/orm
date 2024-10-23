<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ReadonlyProperties;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\Models\ValueObjects\Uuid;

#[Entity]
class Library
{
    #[Column]
    #[Id]
    #[GeneratedValue(strategy: 'NONE')]
    public readonly Uuid $uuid;
}
