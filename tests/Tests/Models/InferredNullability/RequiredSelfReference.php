<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\InferredNullability;

use Doctrine\ORM\Mapping as ORM;

/** The non-nullable PHP type makes the join column NOT NULL. */
#[ORM\Entity]
class RequiredSelfReference
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    public self $ref;
}
