<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\BigIntegers;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class BigIntegers
{
    #[ORM\Column]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    public int $one = 1;

    #[ORM\Column(type: Types::BIGINT)]
    public string $two = '2';

    #[ORM\Column(type: Types::BIGINT)]
    public float $three = 3.0;
}
