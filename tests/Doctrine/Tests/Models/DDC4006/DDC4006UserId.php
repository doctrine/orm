<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC4006;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

#[Embeddable]
class DDC4006UserId
{
    #[Id]
    #[GeneratedValue('IDENTITY')]
    #[Column(type: 'integer')]
    private int $id;
}
