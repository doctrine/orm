<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\InferredNullability;

use Doctrine\ORM\Mapping as ORM;

/** The nullable PHP type keeps the join column nullable. */
#[ORM\Entity]
class OptionalSelfReference
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    public self|null $ref = null;
}
